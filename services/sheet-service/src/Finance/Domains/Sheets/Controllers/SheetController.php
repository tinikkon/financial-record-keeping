<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Controllers;

use Finance\Domains\Auth\Exceptions\InvalidAccessTokenException;
use Finance\Domains\Auth\Support\CurrentUser;
use Finance\Domains\Sheets\Actions\CreateSheetAction;
use Finance\Domains\Sheets\Actions\FindAvailableSheetAction;
use Finance\Domains\Sheets\Actions\RenameSheetAction;
use Finance\Domains\Sheets\Actions\ReorderSheetsAction;
use Finance\Domains\Sheets\Actions\UpdateColumnWidthsAction;
use Finance\Domains\Sheets\Contracts\SheetRepositoryContract;
use Finance\Domains\Sheets\Exceptions\SheetNameAlreadyUsedException;
use Finance\Domains\Sheets\Exceptions\SheetNotAvailableException;
use Finance\Domains\Sheets\Models\SheetModel;
use Finance\Domains\Sheets\Requests\CreateSheetRequest;
use Finance\Domains\Sheets\Requests\ReorderSheetsRequest;
use Finance\Domains\Sheets\Requests\UpdateSheetRequest;
use Finance\Domains\Sheets\Resources\SheetResource;
use Finance\Domains\Workbooks\Actions\FindAvailableWorkbookAction;
use Finance\Domains\Workbooks\Exceptions\WorkbookNotAvailableException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        SheetRepositoryContract $sheets,
    ): JsonResponse {
        $sheet = $findSheet->execute($sheetIdentifier, CurrentUser::identifier($request));
        $sheets->delete($sheet->identifier());

        return new JsonResponse(status: JsonResponse::HTTP_NO_CONTENT);
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
}
