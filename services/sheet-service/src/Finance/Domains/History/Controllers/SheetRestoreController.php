<?php

declare(strict_types=1);

namespace Finance\Domains\History\Controllers;

use Finance\Domains\Auth\Exceptions\InvalidAccessTokenException;
use Finance\Domains\Auth\Support\CurrentUser;
use Finance\Domains\Cells\Exceptions\InvalidFormulaException;
use Finance\Domains\Cells\Resources\CellResource;
use Finance\Domains\History\Actions\RestoreSheetStateAction;
use Finance\Domains\History\Exceptions\HistoryUnavailableException;
use Finance\Domains\History\Requests\RestoreSheetRequest;
use Finance\Domains\Messaging\Actions\PublishCellsChangedAction;
use Finance\Domains\Realtime\Actions\BroadcastCellsChangedAction;
use Finance\Domains\Sheets\Actions\FindAvailableSheetAction;
use Finance\Domains\Sheets\Exceptions\SheetNotAvailableException;
use Finance\FormulaEngine\Exceptions\InvalidReferenceException;
use Illuminate\Http\JsonResponse;

final readonly class SheetRestoreController
{
    /**
     * @throws InvalidAccessTokenException
     * @throws SheetNotAvailableException
     * @throws HistoryUnavailableException
     * @throws InvalidFormulaException
     * @throws InvalidReferenceException
     */
    public function restore(
        RestoreSheetRequest $request,
        string $sheetIdentifier,
        FindAvailableSheetAction $findSheet,
        RestoreSheetStateAction $restoreState,
        BroadcastCellsChangedAction $broadcastChanges,
        PublishCellsChangedAction $publishChanges,
    ): JsonResponse {
        $userIdentifier = CurrentUser::identifier($request);
        $sheet = $findSheet->execute($sheetIdentifier, $userIdentifier);

        $applied = $restoreState->execute(
            $sheet,
            $request->version(),
            $userIdentifier,
            (string) $request->bearerToken(),
        );

        $broadcastChanges->execute($sheetIdentifier, $applied->sheetVersion, $applied->cells, $userIdentifier);
        $publishChanges->execute($sheet, $applied->sheetVersion, $applied->cells, $userIdentifier);

        return new JsonResponse([
            'sheetVersion' => $applied->sheetVersion,
            'cells' => array_map(CellResource::toArray(...), $applied->cells),
        ]);
    }
}
