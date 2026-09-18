<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Ast;

final readonly class FunctionCallNode implements Node
{
    /**
     * @param list<Node> $arguments
     */
    public function __construct(
        public string $name,
        public array $arguments,
    ) {
    }
}
