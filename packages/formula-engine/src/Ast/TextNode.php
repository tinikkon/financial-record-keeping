<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Ast;

final readonly class TextNode implements Node
{
    public function __construct(public string $value)
    {
    }
}
