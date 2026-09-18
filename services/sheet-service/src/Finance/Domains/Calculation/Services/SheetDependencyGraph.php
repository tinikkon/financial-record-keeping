<?php

declare(strict_types=1);

namespace Finance\Domains\Calculation\Services;

use Finance\Domains\Cells\Contracts\CellRepositoryContract;
use Finance\Domains\Cells\Models\CellModel;
use Finance\FormulaEngine\Recalculation\DependencyGraph;
use Finance\FormulaEngine\Values\CellReference;

/**
 * Обратные связи листа, читаемые из базы по индексу зависимостей.
 */
final readonly class SheetDependencyGraph implements DependencyGraph
{
    public function __construct(
        private CellRepositoryContract $cells,
        private string $sheetIdentifier,
    ) {
    }

    public function dependentsOf(CellReference $reference): array
    {
        return $this->cells->dependentsOf($this->sheetIdentifier, $reference)
            ->map(static fn (CellModel $cell): CellReference => $cell->reference())
            ->values()
            ->all();
    }
}
