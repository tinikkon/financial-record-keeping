<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Actions;

use Finance\Domains\Calculation\Actions\ApplyCellEditsAction;
use Finance\Domains\Cells\Actions\CellEdit;
use Finance\Domains\Cells\Contracts\CellRepositoryContract;
use Finance\Domains\Cells\Enums\CellKind;
use Finance\Domains\Cells\Exceptions\InvalidFormulaException;
use Finance\Domains\Cells\Models\CellModel;
use Finance\Domains\Sheets\Contracts\SheetRepositoryContract;
use Finance\Domains\Sheets\Exceptions\SheetNameAlreadyUsedException;
use Finance\Domains\Sheets\Models\SheetModel;

/**
 * Заводит новый месяц копией предыдущего.
 *
 * Переносятся заголовки, формулы и оформление; числа не переносятся — новый месяц
 * начинается пустым. Формулы после переноса сразу вычисляются, поэтому итоговые
 * строки нового листа показывают нули, а не остатки прошлого месяца.
 */
final readonly class DuplicateSheetAction
{
    public function __construct(
        private SheetRepositoryContract $sheets,
        private CellRepositoryContract $cells,
        private ApplyCellEditsAction $applyEdits,
    ) {
    }

    /**
     * @throws SheetNameAlreadyUsedException
     * @throws InvalidFormulaException
     */
    public function execute(SheetModel $source, string $name, string $userIdentifier): SheetModel
    {
        if ($this->sheets->findByName($source->workbook_id, $name) !== null) {
            throw new SheetNameAlreadyUsedException($name);
        }

        $copy = $this->sheets->create(
            workbookIdentifier: $source->workbook_id,
            name: $name,
            position: $this->sheets->nextPosition($source->workbook_id),
            rowCount: $source->row_count,
            columnCount: $source->column_count,
            columnWidths: $source->column_widths ?? [],
        );

        $sourceCells = $this->cells->forSheet($source->identifier());

        foreach ($sourceCells as $cell) {
            $format = $cell->format ?? [];

            if ($format === [] && $cell->cellKind() !== CellKind::Text) {
                continue;
            }

            $this->cells->save($copy->identifier(), $cell->reference(), [
                'kind' => $cell->cellKind() === CellKind::Text ? CellKind::Text->value : CellKind::Empty->value,
                'input' => $cell->cellKind() === CellKind::Text ? $cell->input : null,
                'value_text' => $cell->cellKind() === CellKind::Text ? $cell->value_text : null,
                'format' => $format,
                'updated_by' => $userIdentifier,
            ]);
        }

        $formulaEdits = $sourceCells
            ->filter(static fn (CellModel $cell): bool => $cell->cellKind() === CellKind::Formula)
            ->map(static fn (CellModel $cell): CellEdit => new CellEdit($cell->reference(), $cell->input))
            ->values()
            ->all();

        if ($formulaEdits !== []) {
            $this->applyEdits->execute($copy, $formulaEdits, $userIdentifier);
        }

        $refreshed = $this->sheets->findByIdentifier($copy->identifier());

        return $refreshed ?? $copy;
    }
}
