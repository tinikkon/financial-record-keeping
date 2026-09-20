<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Editing;

use Finance\FormulaEngine\Exceptions\InvalidReferenceException;
use Finance\FormulaEngine\Exceptions\SyntaxErrorException;
use Finance\FormulaEngine\Lexing\Lexer;
use Finance\FormulaEngine\Lexing\Token;
use Finance\FormulaEngine\Lexing\TokenType;
use Finance\FormulaEngine\Values\CellReference;

/**
 * Переписывает ссылки формулы после вставки или удаления строк.
 *
 * Правится только текст ссылок, всё остальное остаётся как было: пробелы,
 * названия функций, строки в кавычках. Поэтому формула разбирается на лексемы
 * и подменяются те из них, что оказались ссылками, — разбор в дерево и печать
 * обратно переписали бы формулу целиком, и человек не узнал бы своё.
 *
 * Закрепление знаком доллара на сдвиг не влияет: `$A$5` после вставки строки
 * выше становится `$A$6`. Доллар удерживает ссылку при копировании формулы,
 * а не при перестройке листа, и так же ведёт себя Excel.
 */
final readonly class RowShiftRewriter
{
    public function __construct(private Lexer $lexer)
    {
    }

    /**
     * Формула после вставки строк перед указанной.
     *
     * @throws SyntaxErrorException
     * @throws InvalidReferenceException
     */
    public function afterInsert(string $formula, int $atRow, int $count): string
    {
        $rewritten = $this->rewrite($formula, static function (int $row) use ($atRow, $count): int {
            return $row >= $atRow ? $row + $count : $row;
        });

        // Вставка ничего не ломает: ссылки только отъезжают вниз.
        return $rewritten ?? $formula;
    }

    /**
     * Формула после удаления строк, начиная с указанной.
     *
     * Ссылка на удалённую строку неисправима: возвращается null, и вызывающему
     * остаётся заменить содержимое ячейки ошибкой. Диапазон, задетый частично,
     * просто сжимается — в нём пропали строки, а не опора.
     *
     * @throws SyntaxErrorException
     * @throws InvalidReferenceException
     */
    public function afterDelete(string $formula, int $fromRow, int $count): ?string
    {
        $afterBand = $fromRow + $count;

        return $this->rewrite(
            $formula,
            static function (int $row) use ($fromRow, $afterBand, $count): ?int {
                if ($row < $fromRow) {
                    return $row;
                }

                return $row >= $afterBand ? $row - $count : null;
            },
            static function (int $row, bool $isRangeStart) use ($fromRow, $afterBand, $count): int {
                if ($row < $fromRow) {
                    return $row;
                }

                if ($row >= $afterBand) {
                    return $row - $count;
                }

                // Край диапазона упёрся в удалённое: он придвигается к месту,
                // где теперь сходятся уцелевшие строки.
                return $isRangeStart ? $fromRow : $fromRow - 1;
            },
        );
    }

    /**
     * @param callable(int): ?int             $shiftRow      новая строка одиночной ссылки
     * @param (callable(int, bool): ?int)|null $shiftRangeRow новая строка края диапазона
     *
     * @throws SyntaxErrorException
     * @throws InvalidReferenceException
     */
    private function rewrite(string $formula, callable $shiftRow, ?callable $shiftRangeRow = null): ?string
    {
        $tokens = $this->lexer->tokenize($this->body($formula));
        $replacements = [];

        foreach ($tokens as $position => $token) {
            if (! $token->is(TokenType::CellReference)) {
                continue;
            }

            $rangeSide = $this->rangeSide($tokens, $position);
            $shifted = $rangeSide === null || $shiftRangeRow === null
                ? $shiftRow(CellReference::fromString($token->lexeme)->row)
                : $shiftRangeRow(CellReference::fromString($token->lexeme)->row, $rangeSide === 'start');

            if ($shifted === null || $shifted < 1) {
                return null;
            }

            $replacements[] = [$token, $shifted];
        }

        $rewritten = $formula;

        // Замены идут с конца: иначе сдвинувшаяся длина строки сбила бы позиции
        // ещё не заменённых лексем.
        foreach (array_reverse($replacements) as [$token, $row]) {
            $rewritten = $this->replaced($rewritten, $token, $row);
        }

        return $this->rangesStillValid($rewritten) ? $rewritten : null;
    }

    /**
     * Какой стороной диапазона стоит ссылка, если она вообще его сторона.
     *
     * @param list<Token> $tokens
     *
     * @return 'start'|'end'|null
     */
    private function rangeSide(array $tokens, int $position): ?string
    {
        if (($tokens[$position + 1] ?? null)?->is(TokenType::Colon) === true) {
            return 'start';
        }

        if (($tokens[$position - 1] ?? null)?->is(TokenType::Colon) === true) {
            return 'end';
        }

        return null;
    }

    /**
     * @throws InvalidReferenceException
     */
    private function replaced(string $formula, Token $token, int $row): string
    {
        $reference = CellReference::fromString($token->lexeme);
        $updated = new CellReference(
            column: $reference->column,
            row: $row,
            columnFixed: $reference->columnFixed,
            rowFixed: $reference->rowFixed,
        );

        // Позиции лексем считаны от тела формулы, а ведущий знак равенства
        // в него не входит.
        $offset = $token->position + (strlen($formula) - strlen($this->body($formula)));

        return substr_replace($formula, $updated->toString(), $offset, strlen($token->lexeme));
    }

    /**
     * Диапазон, у которого конец уехал выше начала, означает, что от него
     * ничего не осталось.
     *
     * @throws SyntaxErrorException
     * @throws InvalidReferenceException
     */
    private function rangesStillValid(string $formula): bool
    {
        $tokens = $this->lexer->tokenize($this->body($formula));

        foreach ($tokens as $position => $token) {
            if ($this->rangeSide($tokens, $position) !== 'start') {
                continue;
            }

            $endToken = $tokens[$position + 2] ?? null;

            if ($endToken === null) {
                continue;
            }

            $start = CellReference::fromString($token->lexeme);
            $end = CellReference::fromString($endToken->lexeme);

            if ($end->row < $start->row) {
                return false;
            }
        }

        return true;
    }

    private function body(string $formula): string
    {
        $body = ltrim($formula);

        return str_starts_with($body, '=') ? substr($body, 1) : $body;
    }
}
