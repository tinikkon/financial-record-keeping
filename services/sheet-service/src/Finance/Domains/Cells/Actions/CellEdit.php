<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Actions;

use Finance\FormulaEngine\Values\CellReference;

/**
 * Одна правка: что пользователь ввёл и в какую ячейку.
 */
final readonly class CellEdit
{
    public function __construct(
        public CellReference $reference,
        public ?string $input,
    ) {
    }
}
