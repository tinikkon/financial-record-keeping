<?php

declare(strict_types=1);

namespace Finance\Domains\History\Actions;

use Finance\Domains\Calculation\Actions\ApplyCellEditsAction;
use Finance\Domains\Cells\Actions\AppliedCellEdits;
use Finance\Domains\Cells\Actions\CellEdit;
use Finance\Domains\Cells\Contracts\CellRepositoryContract;
use Finance\Domains\Cells\Exceptions\InvalidFormulaException;
use Finance\Domains\Cells\Models\CellModel;
use Finance\Domains\History\Contracts\SheetHistoryDriverContract;
use Finance\Domains\History\Exceptions\HistoryUnavailableException;
use Finance\Domains\Sheets\Models\SheetModel;
use Finance\FormulaEngine\Exceptions\InvalidReferenceException;
use Finance\FormulaEngine\Values\CellReference;

/**
 * Возвращает лист к состоянию на прошлую версию.
 *
 * Прошлое не переписывается: восстановление применяется как обычная пачка правок
 * и само попадает в журнал новой версией. Отменить восстановление можно тем же
 * способом, что и любую другую правку.
 */
final readonly class RestoreSheetStateAction
{
    public function __construct(
        private SheetHistoryDriverContract $history,
        private CellRepositoryContract $cells,
        private ApplyCellEditsAction $applyEdits,
    ) {
    }

    /**
     * @throws HistoryUnavailableException
     * @throws InvalidFormulaException
     * @throws InvalidReferenceException
     */
    public function execute(SheetModel $sheet, int $version, string $userIdentifier, string $accessToken): AppliedCellEdits
    {
        $restored = $this->history->stateAtVersion($sheet->identifier(), $version, $accessToken);

        $currentAddresses = $this->cells->forSheet($sheet->identifier())
            ->reject(static fn (CellModel $cell): bool => $cell->input === null)
            ->map(static fn (CellModel $cell): string => $cell->address())
            ->all();

        $edits = [];

        // Ячейки, заполненные сейчас, но пустые в прошлом, надо очистить —
        // иначе восстановление оставило бы лишнее от более поздних правок.
        foreach (array_unique([...$currentAddresses, ...array_keys($restored)]) as $address) {
            $edits[] = new CellEdit(
                CellReference::fromString($address),
                $restored[$address]['input'] ?? null,
            );
        }

        return $this->applyEdits->execute($sheet, $edits, $userIdentifier);
    }
}
