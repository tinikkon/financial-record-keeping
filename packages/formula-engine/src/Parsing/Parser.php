<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Parsing;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Finance\FormulaEngine\Ast\BinaryOperationNode;
use Finance\FormulaEngine\Ast\FunctionCallNode;
use Finance\FormulaEngine\Ast\Node;
use Finance\FormulaEngine\Ast\NumberNode;
use Finance\FormulaEngine\Ast\RangeNode;
use Finance\FormulaEngine\Ast\ReferenceNode;
use Finance\FormulaEngine\Ast\TextNode;
use Finance\FormulaEngine\Ast\UnaryOperationNode;
use Finance\FormulaEngine\Exceptions\InvalidReferenceException;
use Finance\FormulaEngine\Exceptions\SyntaxErrorException;
use Finance\FormulaEngine\Lexing\Token;
use Finance\FormulaEngine\Lexing\TokenType;
use Finance\FormulaEngine\Values\CellRange;
use Finance\FormulaEngine\Values\CellReference;

/**
 * Рекурсивный спуск по грамматике формул.
 *
 * Приоритеты, от низшего к высшему: сложение и вычитание, умножение и деление,
 * возведение в степень, унарный знак. Порядок унарного минуса и степени взят
 * из Excel: там -2^2 равно четырём, а не минус четырём.
 */
final class Parser
{
    /** @var list<Token> */
    private array $tokens = [];

    private int $position = 0;

    /**
     * @param list<Token> $tokens
     *
     * @throws SyntaxErrorException
     */
    public function parse(array $tokens): Node
    {
        $this->tokens = $tokens;
        $this->position = 0;

        $expression = $this->parseAdditive();

        if (! $this->current()->is(TokenType::EndOfInput)) {
            throw new SyntaxErrorException(
                "Лишнее в формуле после позиции {$this->current()->position}",
                $this->current()->position,
            );
        }

        return $expression;
    }

    /**
     * @throws SyntaxErrorException
     */
    private function parseAdditive(): Node
    {
        $left = $this->parseMultiplicative();

        while ($this->current()->is(TokenType::Plus, TokenType::Minus)) {
            $operator = $this->advance()->lexeme;
            $left = new BinaryOperationNode($operator, $left, $this->parseMultiplicative());
        }

        return $left;
    }

    /**
     * @throws SyntaxErrorException
     */
    private function parseMultiplicative(): Node
    {
        $left = $this->parseExponent();

        while ($this->current()->is(TokenType::Asterisk, TokenType::Slash)) {
            $operator = $this->advance()->lexeme;
            $left = new BinaryOperationNode($operator, $left, $this->parseExponent());
        }

        return $left;
    }

    /**
     * Степень правоассоциативна: 2^3^2 читается как 2^(3^2).
     *
     * @throws SyntaxErrorException
     */
    private function parseExponent(): Node
    {
        $base = $this->parseUnary();

        if (! $this->current()->is(TokenType::Caret)) {
            return $base;
        }

        $this->advance();

        return new BinaryOperationNode('^', $base, $this->parseExponent());
    }

    /**
     * @throws SyntaxErrorException
     */
    private function parseUnary(): Node
    {
        if ($this->current()->is(TokenType::Minus, TokenType::Plus)) {
            $operator = $this->advance()->lexeme;

            return new UnaryOperationNode($operator, $this->parseUnary());
        }

        return $this->parsePrimary();
    }

    /**
     * @throws SyntaxErrorException
     */
    private function parsePrimary(): Node
    {
        $token = $this->current();

        return match (true) {
            $token->is(TokenType::Number) => $this->parseNumber(),
            $token->is(TokenType::Text) => new TextNode($this->advance()->lexeme),
            $token->is(TokenType::CellReference) => $this->parseReferenceOrRange(),
            $token->is(TokenType::Identifier) => $this->parseFunctionCall(),
            $token->is(TokenType::OpeningParenthesis) => $this->parseParenthesised(),
            default => throw new SyntaxErrorException(
                "Формула обрывается или содержит лишний знак в позиции {$token->position}",
                $token->position,
            ),
        };
    }

    /**
     * @throws SyntaxErrorException
     */
    private function parseNumber(): NumberNode
    {
        $token = $this->advance();

        try {
            return new NumberNode(BigDecimal::of($token->lexeme));
        } catch (MathException $exception) {
            throw new SyntaxErrorException(
                "Не удалось прочитать число «{$token->lexeme}»",
                $token->position,
                $exception,
            );
        }
    }

    /**
     * @throws SyntaxErrorException
     */
    private function parseReferenceOrRange(): Node
    {
        $startToken = $this->advance();
        $start = $this->toReference($startToken);

        if (! $this->current()->is(TokenType::Colon)) {
            return new ReferenceNode($start);
        }

        $this->advance();
        $endToken = $this->expect(TokenType::CellReference, 'После двоеточия ожидается вторая граница диапазона');

        return new RangeNode(new CellRange($start, $this->toReference($endToken)));
    }

    /**
     * @throws SyntaxErrorException
     */
    private function parseFunctionCall(): FunctionCallNode
    {
        $nameToken = $this->advance();
        $this->expect(TokenType::OpeningParenthesis, "После имени «{$nameToken->lexeme}» ожидается открывающая скобка");

        $arguments = [];

        if (! $this->current()->is(TokenType::ClosingParenthesis)) {
            $arguments[] = $this->parseAdditive();

            while ($this->current()->is(TokenType::ArgumentSeparator)) {
                $this->advance();
                $arguments[] = $this->parseAdditive();
            }
        }

        $this->expect(TokenType::ClosingParenthesis, 'Не хватает закрывающей скобки');

        return new FunctionCallNode($nameToken->lexeme, $arguments);
    }

    /**
     * @throws SyntaxErrorException
     */
    private function parseParenthesised(): Node
    {
        $this->advance();
        $expression = $this->parseAdditive();
        $this->expect(TokenType::ClosingParenthesis, 'Не хватает закрывающей скобки');

        return $expression;
    }

    /**
     * @throws SyntaxErrorException
     */
    private function toReference(Token $token): CellReference
    {
        try {
            return CellReference::fromString($token->lexeme);
        } catch (InvalidReferenceException $exception) {
            throw new SyntaxErrorException($exception->getMessage(), $token->position, $exception);
        }
    }

    /**
     * @throws SyntaxErrorException
     */
    private function expect(TokenType $type, string $message): Token
    {
        if (! $this->current()->is($type)) {
            throw new SyntaxErrorException($message, $this->current()->position);
        }

        return $this->advance();
    }

    private function current(): Token
    {
        return $this->tokens[$this->position];
    }

    private function advance(): Token
    {
        return $this->tokens[$this->position++];
    }
}
