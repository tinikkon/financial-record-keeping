<?php

declare(strict_types=1);

namespace Finance\FormulaEngine;

use Finance\FormulaEngine\Ast\Node;
use Finance\FormulaEngine\Contracts\CellValueResolver;
use Finance\FormulaEngine\Dependencies\CellDependencies;
use Finance\FormulaEngine\Dependencies\DependencyExtractor;
use Finance\FormulaEngine\Evaluation\Evaluator;
use Finance\FormulaEngine\Evaluation\FunctionRegistry;
use Finance\FormulaEngine\Exceptions\SyntaxErrorException;
use Finance\FormulaEngine\Lexing\Lexer;
use Finance\FormulaEngine\Parsing\Parser;
use Finance\FormulaEngine\Values\CellValue;

/**
 * Единая точка входа в движок формул.
 */
final class FormulaEngine
{
    public function __construct(
        private readonly Lexer $lexer,
        private readonly Parser $parser,
        private readonly Evaluator $evaluator,
        private readonly DependencyExtractor $dependencyExtractor,
    ) {
    }

    public static function create(): self
    {
        return new self(
            new Lexer(),
            new Parser(),
            new Evaluator(new FunctionRegistry()),
            new DependencyExtractor(),
        );
    }

    /**
     * Ведущий знак равенства необязателен: формула из базы приходит вместе с ним,
     * а внутренние вызовы удобнее делать без него.
     *
     * @throws SyntaxErrorException
     */
    public function parse(string $formula): Node
    {
        $body = ltrim($formula);
        $body = str_starts_with($body, '=') ? substr($body, 1) : $body;

        return $this->parser->parse($this->lexer->tokenize($body));
    }

    /**
     * @throws SyntaxErrorException
     */
    public function evaluate(string $formula, CellValueResolver $resolver): CellValue
    {
        return $this->evaluator->evaluate($this->parse($formula), $resolver);
    }

    public function evaluateNode(Node $node, CellValueResolver $resolver): CellValue
    {
        return $this->evaluator->evaluate($node, $resolver);
    }

    /**
     * @throws SyntaxErrorException
     */
    public function dependencies(string $formula): CellDependencies
    {
        return $this->dependencyExtractor->extract($this->parse($formula));
    }

    public function dependenciesOfNode(Node $node): CellDependencies
    {
        return $this->dependencyExtractor->extract($node);
    }
}
