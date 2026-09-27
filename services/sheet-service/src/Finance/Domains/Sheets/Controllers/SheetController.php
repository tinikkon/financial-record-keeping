<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Controllers;

use Finance\Domains\Auth\Exceptions\InvalidAccessTokenException;
use Finance\Domains\Auth\Support\CurrentUser;
use Finance\Domains\Cells\Exceptions\InvalidFormulaException;
use Finance\Domains\Sheets\Actions\CreateSheetAction;
use Finance\Domains\Sheets\Actions\DeleteSheetAction;
use Finance\Domains\Sheets\Actions\DuplicateSheetAction;
use Finance\Domains\Sheets\Actions\ExportSheetToCsvAction;
use Finance\Domains\Sheets\Actions\FindAvailableSheetAction;
use Finance\Domains\Sheets\Actions\RenameSheetAction;
use Finance\Domains\Sheets\Actions\ReorderSheetsAction;
use Finance\Domains\Sheets\Actions\ShiftSheetLinesAction;
use Finance\Domains\Sheets\Actions\UpdateColumnWidthsAction;
use Finance\Domains\Sheets\Contracts\SheetRepositoryContract;
use Finance\Domains\Sheets\Exceptions\SheetNameAlreadyUsedException;
use Finance\Domains\Sheets\Exceptions\CellsWouldOverflowSheetException;
use Finance\Domains\Sheets\Exceptions\SheetNotAvailableException;
use Finance\Domains\Sheets\Models\SheetModel;
use Finance\Domains\Sheets\Requests\CreateSheetRequest;
use Finance\Domains\Sheets\Requests\ReorderSheetsRequest;
use Finance\Domains\Sheets\Requests\ShiftColumnsRequest;
use Finance\Domains\Sheets\Requests\ShiftRowsRequest;
use Finance\Domains\Sheets\Requests\UpdateSheetRequest;
use Finance\Domains\Sheets\Resources\SheetResource;
use Finance\Domains\Cells\Data\AppliedCellEdits;
use Finance\Domains\Cells\Resources\AppliedCellEditsResource;
use Finance\Domains\Messaging\Actions\PublishCellsChangedAction;
use Finance\Domains\Realtime\Actions\BroadcastCellsChangedAction;
use Finance\Domains\Workbooks\Actions\FindAvailableWorkbookAction;
use Finance\Domains\Workbooks\Exceptions\WorkbookNotAvailableException;
use Finance\FormulaEngine\Editing\ShiftAxis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final readonly class SheetController
{
    /**
     * @throws InvalidAccessTokenException
     * @throws WorkbookNotAvailableException
     */
    public function index(
        Request $request,
        string $workbookIdentifier,
        FindAvailableWorkbookAction $findWorkbook,
        SheetRepositoryContract $sheets,
    ): JsonResponse {
        $findWorkbook->execute($workbookIdentifier, CurrentUser::identifier($request));

        $listed = $sheets->forWorkbook($workbookIdentifier)
            ->map(static fn (SheetModel $sheet): array => SheetResource::toArray($sheet))
            ->values()
            ->all();

        return new JsonResponse(['sheets' => $listed]);
    }

    /**
     * @throws InvalidAccessTokenException
     * @throws WorkbookNotAvailableException
     * @throws SheetNameAlreadyUsedException
     */
    public function store(
        CreateSheetRequest $request,
        string $workbookIdentifier,
        FindAvailableWorkbookAction $findWorkbook,
        CreateSheetAction $createSheet,
    ): JsonResponse {
        $findWorkbook->execute($workbookIdentifier, CurrentUser::identifier($request));

        $sheet = $createSheet->execute($workbookIdentifier, $request->name());

        return new JsonResponse(SheetResource::toArray($sheet), JsonResponse::HTTP_CREATED);
    }

    /**
     * @throws InvalidAccessTokenException
     * @throws SheetNotAvailableException
     * @throws SheetNameAlreadyUsedException
     */
    public function update(
        UpdateSheetRequest $request,
        string $sheetIdentifier,
        FindAvailableSheetAction $findSheet,
        RenameSheetAction $renameSheet,
        UpdateColumnWidthsAction $updateColumnWidths,
        SheetRepositoryContract $sheets,
    ): JsonResponse {
        $sheet = $findSheet->execute($sheetIdentifier, CurrentUser::identifier($request));

        $newName = $request->newName();

        if ($newName !== null) {
            $renameSheet->execute($sheet, $newName);
        }

        $columnWidths = $request->columnWidths();

        if ($columnWidths !== null) {
            $updateColumnWidths->execute($sheet, $columnWidths);
        }

        $updated = $sheets->findByIdentifier($sheetIdentifier);

        if ($updated === null) {
            throw new SheetNotAvailableException();
        }

        return new JsonResponse(SheetResource::toArray($updated));
    }

    /**
     * @throws InvalidAccessTokenException
     * @throws SheetNotAvailableException
     */
    public function destroy(
        Request $request,
        string $sheetIdentifier,
        FindAvailableSheetAction $findSheet,
        DeleteSheetAction $deleteSheet,
    ): JsonResponse {
        $sheet = $findSheet->execute($sheetIdentifier, CurrentUser::identifier($request));
        $deleteSheet->execute($sheet);

        return new JsonResponse(status: JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @throws InvalidAccessTokenException
     * @throws SheetNotAvailableException
     * @throws SheetNameAlreadyUsedException
     * @throws InvalidFormulaException
     */
    public function duplicate(
        CreateSheetRequest $request,
        string $sheetIdentifier,
        FindAvailableSheetAction $findSheet,
        DuplicateSheetAction $duplicateSheet,
    ): JsonResponse {
        $userIdentifier = CurrentUser::identifier($request);
        $source = $findSheet->execute($sheetIdentifier, $userIdentifier);

        $copy = $duplicateSheet->execute($source, $request->name(), $userIdentifier);

        return new JsonResponse(SheetResource::toArray($copy), JsonResponse::HTTP_CREATED);
    }

    /**
     * @throws InvalidAccessTokenException
     * @throws WorkbookNotAvailableException
     */
    public function reorder(
        ReorderSheetsRequest $request,
        string $workbookIdentifier,
        FindAvailableWorkbookAction $findWorkbook,
        ReorderSheetsAction $reorderSheets,
        SheetRepositoryContract $sheets,
    ): JsonResponse {
        $findWorkbook->execute($workbookIdentifier, CurrentUser::identifier($request));
        $reorderSheets->execute($workbookIdentifier, $request->sheetIdentifiers());

        $ordered = $sheets->forWorkbook($workbookIdentifier)
            ->map(static fn (SheetModel $sheet): array => SheetResource::toArray($sheet))
            ->values()
            ->all();

        return new JsonResponse(['sheets' => $ordered]);
    }

    /**
     * Вставляет строки перед указанной; всё, что ниже, съезжает вниз.
     *
     * @throws InvalidAccessTokenException
     * @throws SheetNotAvailableException
     * @throws CellsWouldOverflowSheetException
     * @throws InvalidFormulaException
     */
    public function insertRows(
        ShiftRowsRequest $request,
        string $sheetIdentifier,
        FindAvailableSheetAction $findSheet,
        ShiftSheetLinesAction $shiftLines,
        BroadcastCellsChangedAction $broadcastChanges,
        PublishCellsChangedAction $publishChanges,
    ): JsonResponse {
        $userIdentifier = CurrentUser::identifier($request);
        $sheet = $findSheet->execute($sheetIdentifier, $userIdentifier);

        $applied = $shiftLines->insert($sheet, ShiftAxis::Rows, $request->row(), $request->count(), $userIdentifier);

        return $this->applied($sheet, $applied, $userIdentifier, $broadcastChanges, $publishChanges);
    }

    /**
     * Удаляет строки; всё, что ниже, поднимается на их место.
     *
     * @throws InvalidAccessTokenException
     * @throws SheetNotAvailableException
     * @throws CellsWouldOverflowSheetException
     * @throws InvalidFormulaException
     */
    public function deleteRows(
        ShiftRowsRequest $request,
        string $sheetIdentifier,
        FindAvailableSheetAction $findSheet,
        ShiftSheetLinesAction $shiftLines,
        BroadcastCellsChangedAction $broadcastChanges,
        PublishCellsChangedAction $publishChanges,
    ): JsonResponse {
        $userIdentifier = CurrentUser::identifier($request);
        $sheet = $findSheet->execute($sheetIdentifier, $userIdentifier);

        $applied = $shiftLines->delete($sheet, ShiftAxis::Rows, $request->row(), $request->count(), $userIdentifier);

        return $this->applied($sheet, $applied, $userIdentifier, $broadcastChanges, $publishChanges);
    }

    /**
     * Вставляет колонки перед указанной; всё, что правее, съезжает вправо.
     *
     * @throws InvalidAccessTokenException
     * @throws SheetNotAvailableException
     * @throws CellsWouldOverflowSheetException
     * @throws InvalidFormulaException
     */
    public function insertColumns(
        ShiftColumnsRequest $request,
        string $sheetIdentifier,
        FindAvailableSheetAction $findSheet,
        ShiftSheetLinesAction $shiftLines,
        BroadcastCellsChangedAction $broadcastChanges,
        PublishCellsChangedAction $publishChanges,
    ): JsonResponse {
        $userIdentifier = CurrentUser::identifier($request);
        $sheet = $findSheet->execute($sheetIdentifier, $userIdentifier);

        $applied = $shiftLines->insert($sheet, ShiftAxis::Columns, $request->column(), $request->count(), $userIdentifier);

        return $this->applied($sheet, $applied, $userIdentifier, $broadcastChanges, $publishChanges);
    }

    /**
     * Удаляет колонки; всё, что правее, сдвигается на их место.
     *
     * @throws InvalidAccessTokenException
     * @throws SheetNotAvailableException
     * @throws CellsWouldOverflowSheetException
     * @throws InvalidFormulaException
     */
    public function deleteColumns(
        ShiftColumnsRequest $request,
        string $sheetIdentifier,
        FindAvailableSheetAction $findSheet,
        ShiftSheetLinesAction $shiftLines,
        BroadcastCellsChangedAction $broadcastChanges,
        PublishCellsChangedAction $publishChanges,
    ): JsonResponse {
        $userIdentifier = CurrentUser::identifier($request);
        $sheet = $findSheet->execute($sheetIdentifier, $userIdentifier);

        $applied = $shiftLines->delete($sheet, ShiftAxis::Columns, $request->column(), $request->count(), $userIdentifier);

        return $this->applied($sheet, $applied, $userIdentifier, $broadcastChanges, $publishChanges);
    }

    /**
     * Ответ на перестройку листа — тот же, что и на обычную пачку правок:
     * клиент применяет её тем же кодом. Ширины колонок идут рядом:
     * после сдвига колонок они тоже переехали.
     */
    private function applied(
        SheetModel $sheet,
        AppliedCellEdits $applied,
        string $userIdentifier,
        BroadcastCellsChangedAction $broadcastChanges,
        PublishCellsChangedAction $publishChanges,
    ): JsonResponse {
        $broadcastChanges->execute($sheet->identifier(), $applied, $userIdentifier);
        $publishChanges->execute($sheet, $applied, $userIdentifier);

        return new JsonResponse([
            ...AppliedCellEditsResource::toArray($applied),
            'columnWidths' => $sheet->columnWidths()->toArray(),
        ]);
    }

    /**
     * Лист файлом CSV.
     *
     * Имя файла уходит в заголовок дважды: обычным полем для старых программ
     * и полем с кодировкой — иначе кириллица в названии месяца превращается
     * в мусор.
     *
     * @throws InvalidAccessTokenException
     * @throws SheetNotAvailableException
     */
    public function export(
        Request $request,
        string $sheetIdentifier,
        FindAvailableSheetAction $findSheet,
        ExportSheetToCsvAction $exportToCsv,
    ): Response {
        $sheet = $findSheet->execute($sheetIdentifier, CurrentUser::identifier($request));
        $fileName = $sheet->name . '.csv';

        return new Response($exportToCsv->execute($sheet), Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => sprintf(
                'attachment; filename="%s"; filename*=UTF-8\'\'%s',
                preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName),
                rawurlencode($fileName),
            ),
        ]);
    }
}
