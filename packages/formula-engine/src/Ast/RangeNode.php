<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Ast;

use Finance\FormulaEngine\Values\CellRange;

final readonly class RangeNode implements Node
{
    public function __construct(public CellRange $range)
    {
    }
}
