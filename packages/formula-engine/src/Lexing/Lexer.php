<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Lexing;

use Finance\FormulaEngine\Exceptions\SyntaxErrorException;

/**
 * Разбивает текст формулы на лексемы.
 *
 * Разделителем аргументов считаются и точка с запятой, и запятая: первое привычно
 * по русскому Excel, второе — по английскому. Разделитель дробной части при этом
 * всегда точка, иначе запятая означала бы одновременно две разные вещи.
 */
final class Lexer
{
    private const string NUMBER_PATTERN = '/\G\d+(?:\.\d+)?/';

    private const string CELL_REFERENCE_PATTERN = '/\G\$?[A-Za-z]{1,3}\$?\d{1,7}(?![A-Za-z0-9_])/';

    private const string IDENTIFIER_PATTERN = '/\G[\p{L}_][\p{L}\p{N}_.]*/u';

    private const array SINGLE_CHARACTER_TOKENS = [
        '+' => TokenType::Plus,
        '-' => TokenType::Minus,
        '*' => TokenType::Asterisk,
        '/' => TokenType::Slash,
        '^' => TokenType::Caret,
        '(' => TokenType::OpeningParenthesis,
        ')' => TokenType::ClosingParenthesis,
        ';' => TokenType::ArgumentSeparator,
        ',' => TokenType::ArgumentSeparator,
        ':' => TokenType::Colon,
    ];

    /**
     * @return list<Token>
     *
     * @throws SyntaxErrorException
     */
    public function tokenize(string $formula): array
    {
        $tokens = [];
        $offset = 0;
        $length = strlen($formula);

        while ($offset < $length) {
            $character = $formula[$offset];

            if (ctype_space($character)) {
                $offset++;

                continue;
            }

            if ($character === '"') {
                $tokens[] = $this->readText($formula, $offset);

                continue;
            }

            if (isset(self::SINGLE_CHARACTER_TOKENS[$character])) {
                $tokens[] = new Token(self::SINGLE_CHARACTER_TOKENS[$character], $character, $offset);
                $offset++;

                continue;
            }

            if (preg_match(self::CELL_REFERENCE_PATTERN, $formula, $matches, 0, $offset) === 1) {
                $tokens[] = new Token(TokenType::CellReference, $matches[0], $offset);
                $offset += strlen($matches[0]);

                continue;
            }

            if (preg_match(self::NUMBER_PATTERN, $formula, $matches, 0, $offset) === 1) {
                $tokens[] = new Token(TokenType::Number, $matches[0], $offset);
                $offset += strlen($matches[0]);

                continue;
            }

            if (preg_match(self::IDENTIFIER_PATTERN, $formula, $matches, 0, $offset) === 1) {
                $tokens[] = new Token(TokenType::Identifier, $matches[0], $offset);
                $offset += strlen($matches[0]);

                continue;
            }

            throw new SyntaxErrorException(
                "Непонятный символ «{$character}» в позиции {$offset}",
                $offset,
            );
        }

        $tokens[] = new Token(TokenType::EndOfInput, '', $length);

        return $tokens;
    }

    /**
     * Читает строку в кавычках. Две кавычки подряд внутри строки означают
     * одну кавычку в значении.
     *
     * @throws SyntaxErrorException
     */
    private function readText(string $formula, int &$offset): Token
    {
        $start = $offset;
        $length = strlen($formula);
        $value = '';
        $offset++;

        while ($offset < $length) {
            if ($formula[$offset] !== '"') {
                $value .= $formula[$offset];
                $offset++;

                continue;
            }

            $isEscapedQuote = $offset + 1 < $length && $formula[$offset + 1] === '"';

            if (! $isEscapedQuote) {
                $offset++;

                return new Token(TokenType::Text, $value, $start);
            }

            $value .= '"';
            $offset += 2;
        }

        throw new SyntaxErrorException('Незакрытая кавычка в формуле', $start);
    }
}
