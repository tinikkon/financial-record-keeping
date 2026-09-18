<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Controllers;

use Finance\Domains\Auth\Exceptions\InvalidAccessTokenException;
use Finance\Domains\Auth\Support\CurrentUser;
use Finance\Domains\Calculation\Actions\ApplyCellEditsAction;
use Finance\Domains\Cells\Actions\FormatCellsAction;
use Finance\Domains\Cells\Contracts\CellRepositoryContract;
use Finance\Domains\Cells\Exceptions\InvalidFormulaException;
use Finance\Domains\Cells\Models\CellModel;
use Finance\Domains\Cells\Requests\FormatCellsRequest;
use Finance\Domains\Cells\Requests\UpdateCellsRequest;
use Finance\Domains\Cells\Resources\CellResource;
use Finance\Domains\Sheets\Actions\FindAvailableSheetAction;
use Finance\Domains\Sheets\Exceptions\SheetNotAvailableException;
use Finance\Domains\Sheets\Resources\SheetResource;
use Finance\FormulaEngine\Exceptions\InvalidReferenceException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class CellController
{
    /**
     * Лист целиком: описание и все заполненные ячейки. Это то, что клиент
     * запрашивает при открытии и при каждом подозрении на расхождение.
     *
     * @throws InvalidAccessTokenException
     * @throws SheetNotAvailableException
     */
    public function show(
        Request $request,
        string $sheetIdentifier,
        FindAvailableSheetAction $findSheet,
        CellRepositoryContract $cells,
    ): JsonResponse {
        $sheet = $findSheet->execute($sheetIdentifier, CurrentUser::identifier($request));

        return new JsonResponse([
            'sheet' => SheetResource::toArray($sheet),
            'cells' => $cells->forSheet($sheetIdentifier)
                ->map(static fn (CellModel $cell): array => CellResource::toArray($cell))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @throws InvalidAccessTokenException
     * @throws SheetNotAvailableException
     * @throws InvalidFormulaException
     * @throws InvalidReferenceException
     */
    public function update(
        UpdateCellsRequest $request,
        string $sheetIdentifier,
        FindAvailableSheetAction $findSheet,
        ApplyCellEditsAction $applyEdits,
    ): JsonResponse {
        $userIdentifier = CurrentUser::identifier($request);
        $sheet = $findSheet->execute($sheetIdentifier, $userIdentifier);

        $applied = $applyEdits->execute($sheet, $request->edits(), $userIdentifier);

        return new JsonResponse([
            'sheetVersion' => $applied->sheetVersion,
            'cells' => array_map(CellResource::toArray(...), $applied->cells),
        ]);
    }

    /**
     * @throws InvalidAccessTokenException
     * @throws SheetNotAvailableException
     * @throws InvalidReferenceException
     */
    public function format(
        FormatCellsRequest $request,
        string $sheetIdentifier,
        FindAvailableSheetAction $findSheet,
        FormatCellsAction $formatCells,
    ): JsonResponse {
        $sheet = $findSheet->execute($sheetIdentifier, CurrentUser::identifier($request));

        $result = $formatCells->execute($sheet, $request->range(), $request->cellFormat());

        return new JsonResponse([
            'sheetVersion' => $result['version'],
            'cells' => array_map(CellResource::toArray(...), $result['cells']),
        ]);
    }
}
