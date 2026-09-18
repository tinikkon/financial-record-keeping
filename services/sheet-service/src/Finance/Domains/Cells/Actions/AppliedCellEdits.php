<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Actions;

use Finance\Domains\Cells\Models\CellModel;

/**
 * Итог применения правок: новая версия листа и все ячейки, которые изменились,
 * включая пересчитанные итоги.
 */
final readonly class AppliedCellEdits
{
    /**
     * @param list<CellModel> $cells
     */
    public function __construct(
        public int $sheetVersion,
        public array $cells,
    ) {
    }
}
