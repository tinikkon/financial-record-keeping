<?php

declare(strict_types=1);

namespace Finance\Domains\Summaries\Controllers;

use Finance\Domains\Summaries\Actions\BuildWorkbookSummaryAction;
use Finance\Domains\Summaries\Actions\ReconstructSheetStateAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class SummaryController
{
    public function workbook(string $workbookIdentifier, BuildWorkbookSummaryAction $buildSummary): JsonResponse
    {
        return new JsonResponse(['sheets' => $buildSummary->execute($workbookIdentifier)]);
    }

    public function sheetState(
        Request $request,
        string $sheetIdentifier,
        ReconstructSheetStateAction $reconstructState,
    ): JsonResponse {
        $version = $request->integer('version');

        if ($version < 1) {
            return new JsonResponse(['message' => 'Укажите версию листа'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse([
            'sheetVersion' => $version,
            'cells' => $reconstructState->execute($sheetIdentifier, $version),
        ]);
    }
}
