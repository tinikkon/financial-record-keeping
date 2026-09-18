<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Ast;

use Finance\FormulaEngine\Values\CellReference;

final readonly class ReferenceNode implements Node
{
    public function __construct(public CellReference $reference)
    {
    }
}
