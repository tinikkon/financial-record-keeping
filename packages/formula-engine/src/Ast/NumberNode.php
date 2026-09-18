<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Ast;

use Brick\Math\BigDecimal;

final readonly class NumberNode implements Node
{
    public function __construct(public BigDecimal $value)
    {
    }
}
