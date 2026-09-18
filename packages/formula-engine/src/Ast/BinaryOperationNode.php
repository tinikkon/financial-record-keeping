<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Ast;

final readonly class BinaryOperationNode implements Node
{
    public function __construct(
        public string $operator,
        public Node $left,
        public Node $right,
    ) {
    }
}
