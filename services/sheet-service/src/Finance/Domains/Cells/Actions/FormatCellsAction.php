<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Actions;

use Finance\Domains\Cells\Contracts\CellRepositoryContract;
use Finance\Domains\Cells\Enums\CellKind;
use Finance\Domains\Cells\Models\CellModel;
use Finance\Domains\Sheets\Contracts\SheetRepositoryContract;
use Finance\Domains\Sheets\Models\SheetModel;
use Finance\FormulaEngine\Values\CellRange;

/**
 * Красит диапазон ячеек.
 *
 * Свойство со значением null снимается, остальные накладываются поверх прежних:
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
     * @param array<string, mixed> $format
     *
     * @return array{version: int, cells: list<CellModel>}
     */
    public function execute(SheetModel $sheet, CellRange $range, array $format): array
    {
        $sheetIdentifier = $sheet->identifier();
        $existingByAddress = [];

        foreach ($this->cells->findInRange($sheetIdentifier, $range) as $cell) {
            $existingByAddress[$cell->address()] = $cell;
        }

        $updated = [];

        foreach ($range->references() as $reference) {
            $existing = $existingByAddress[$reference->key()] ?? null;
            $merged = $existing === null ? [] : ($existing->format ?? []);

            foreach ($format as $property => $value) {
                if ($value === null) {
                    unset($merged[$property]);

                    continue;
                }

                $merged[$property] = $value;
            }

            // Пустая ячейка без оформления в базе не заводится: пять тысяч пустых
            // документов на лист хранить незачем.
            if ($existing === null && $merged === []) {
                continue;
            }

            $updated[] = $this->cells->save($sheetIdentifier, $reference, [
                'format' => $merged,
                'kind' => $existing === null ? CellKind::Empty->value : $existing->kind,
            ]);
        }

        return [
            'version' => $this->sheets->incrementVersion($sheetIdentifier),
            'cells' => $updated,
        ];
    }
}
