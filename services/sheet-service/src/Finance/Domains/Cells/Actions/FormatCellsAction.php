<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Actions;

use Finance\Domains\Cells\Contracts\CellRepositoryContract;
use Finance\Domains\Cells\Data\AppliedCellEdits;
use Finance\Domains\Cells\Data\CellFormat;
use Finance\Domains\Cells\Data\CellFormatChange;
use Finance\Domains\Cells\Exceptions\CellNotSavedException;
use Finance\Domains\Sheets\Contracts\SheetRepositoryContract;
use Finance\Domains\Sheets\Models\SheetModel;
use Finance\FormulaEngine\Values\CellRange;

/**
 * Красит диапазон ячеек.
 *
 * Правка накладывается поверх прежнего оформления, а не заменяет его:
 * так можно убрать заливку, не сбрасывая заодно жирность.
 */
final readonly class FormatCellsAction
{
    public function __construct(
        private CellRepositoryContract $cells,
        private SheetRepositoryContract $sheets,
    ) {
    }

    /**
     * @throws CellNotSavedException
     */
    public function execute(SheetModel $sheet, CellRange $range, CellFormatChange $change): AppliedCellEdits
    {
        $sheetIdentifier = $sheet->identifier();

        $existingByAddress = [];
        foreach ($this->cells->findInRange($sheetIdentifier, $range) as $cell) {
            $existingByAddress[$cell->address()] = $cell;
        }

        $updated = [];
        foreach ($range->references() as $reference) {
            $existing = $existingByAddress[$reference->key()] ?? null;
            $merged = $change->applyTo($existing?->cellFormat() ?? new CellFormat());

            // Пустая ячейка без оформления в базе не заводится: пять тысяч пустых
            // документов на лист хранить незачем.
            if ($existing === null && $merged->isEmpty()) {
                continue;
            }

            $updated[] = $this->cells->saveFormat($sheetIdentifier, $reference, $merged);
        }

        return new AppliedCellEdits(
            sheetVersion: $this->sheets->incrementVersion($sheetIdentifier),
            cells: $updated,
        );
    }
}
