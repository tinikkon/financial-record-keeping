<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Ast;

final readonly class UnaryOperationNode implements Node
{
    public function __construct(
        public string $operator,
        public Node $operand,
    ) {
    }
}
