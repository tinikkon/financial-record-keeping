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
 * Переписывает ссылки формулы после вставки или удаления строк или колонок.
 *
 * Правится только текст ссылок, всё остальное остаётся как было: пробелы,
 * названия функций, строки в кавычках. Поэтому формула разбирается на лексемы
 * и подменяются те из них, что оказались ссылками, — разбор в дерево и печать
 * обратно переписали бы формулу целиком, и человек не узнал бы своё.
 *
 * Закрепление знаком доллара на сдвиг не влияет: `$A$5` после вставки строки
 * выше становится `$A$6`, а `$B$5` после вставки колонки левее — `$C$5`. Доллар удерживает ссылку при копировании формулы,
 * а не при перестройке листа, и так же ведёт себя Excel.
 */
final readonly class ReferenceShiftRewriter
{
    public function __construct(private Lexer $lexer)
    {
    }

    /**
     * Формула после вставки строк или колонок перед указанной.
     *
     * @throws SyntaxErrorException
     * @throws InvalidReferenceException
     */
    public function afterInsert(string $formula, ShiftAxis $axis, int $at, int $count): string
    {
        $rewritten = $this->rewrite($formula, $axis, static function (int $line) use ($at, $count): int {
            return $line >= $at ? $line + $count : $line;
        });

        // Вставка ничего не ломает: ссылки только отъезжают вниз или вправо.
        return $rewritten ?? $formula;
    }

    /**
     * Формула после удаления строк или колонок, начиная с указанной.
     *
     * Ссылка на удалённую строку или колонку неисправима: возвращается null,
     * и вызывающему остаётся заменить содержимое ячейки ошибкой. Диапазон,
     * задетый частично, просто сжимается — в нём пропали строки, а не опора.
     *
     * @throws SyntaxErrorException
     * @throws InvalidReferenceException
     */
    public function afterDelete(string $formula, ShiftAxis $axis, int $from, int $count): ?string
    {
        $afterBand = $from + $count;

        return $this->rewrite(
            $formula,
            $axis,
            static function (int $line) use ($from, $afterBand, $count): ?int {
                if ($line < $from) {
                    return $line;
                }

                return $line >= $afterBand ? $line - $count : null;
            },
            static function (int $line, bool $isRangeStart) use ($from, $afterBand, $count): int {
                if ($line < $from) {
                    return $line;
                }

                if ($line >= $afterBand) {
                    return $line - $count;
                }

                // Край диапазона упёрся в удалённое: он придвигается к месту,
                // где теперь сходятся уцелевшие строки или колонки.
                return $isRangeStart ? $from : $from - 1;
            },
        );
    }

    /**
     * @param callable(int): ?int              $shiftLine      новая координата одиночной ссылки
     * @param (callable(int, bool): ?int)|null $shiftRangeLine новая координата края диапазона
     *
     * @throws SyntaxErrorException
     * @throws InvalidReferenceException
     */
    private function rewrite(
        string $formula,
        ShiftAxis $axis,
        callable $shiftLine,
        ?callable $shiftRangeLine = null,
    ): ?string {
        $tokens = $this->lexer->tokenize($this->body($formula));
        $replacements = [];

        foreach ($tokens as $position => $token) {
            if (! $token->is(TokenType::CellReference)) {
                continue;
            }

            $rangeSide = $this->rangeSide($tokens, $position);
            $line = $axis->coordinateOf(CellReference::fromString($token->lexeme));
            $shifted = $rangeSide === null || $shiftRangeLine === null
                ? $shiftLine($line)
                : $shiftRangeLine($line, $rangeSide === 'start');

            if ($shifted === null || $shifted < 1) {
                return null;
            }

            $replacements[] = [$token, $shifted];
        }

        $rewritten = $formula;

        // Замены идут с конца: иначе сдвинувшаяся длина строки сбила бы позиции
        // ещё не заменённых лексем.
        foreach (array_reverse($replacements) as [$token, $line]) {
            $rewritten = $this->replaced($rewritten, $axis, $token, $line);
        }

        return $this->rangesStillValid($rewritten, $axis) ? $rewritten : null;
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
    private function replaced(string $formula, ShiftAxis $axis, Token $token, int $line): string
    {
        $updated = $axis->withCoordinate(CellReference::fromString($token->lexeme), $line);

        // Позиции лексем считаны от тела формулы, а ведущий знак равенства
        // в него не входит.
        $offset = $token->position + (strlen($formula) - strlen($this->body($formula)));

        return substr_replace($formula, $updated->toString(), $offset, strlen($token->lexeme));
    }

    /**
     * Диапазон, у которого конец уехал выше или левее начала, означает,
     * что от него ничего не осталось.
     *
     * @throws SyntaxErrorException
     * @throws InvalidReferenceException
     */
    private function rangesStillValid(string $formula, ShiftAxis $axis): bool
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

            if ($axis->coordinateOf($end) < $axis->coordinateOf($start)) {
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
