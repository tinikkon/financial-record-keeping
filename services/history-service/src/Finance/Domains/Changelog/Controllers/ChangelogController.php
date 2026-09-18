<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Controllers;

use Finance\Domains\Changelog\Models\CellChangeModel;
use Finance\Domains\Changelog\Repositories\CellChangeRepository;
use Finance\Domains\Changelog\Resources\CellChangeResource;
use Illuminate\Http\JsonResponse;

final readonly class ChangelogController
{
    public function forSheet(string $sheetIdentifier, CellChangeRepository $changes): JsonResponse
    {
        return new JsonResponse([
            'changes' => $changes->forSheet($sheetIdentifier)
                ->map(static fn (CellChangeModel $change): array => CellChangeResource::toArray($change))
                ->values()
                ->all(),
        ]);
    }

    public function forCell(string $sheetIdentifier, string $address, CellChangeRepository $changes): JsonResponse
    {
        return new JsonResponse([
            'changes' => $changes->forCell($sheetIdentifier, mb_strtoupper($address))
                ->map(static fn (CellChangeModel $change): array => CellChangeResource::toArray($change))
                ->values()
                ->all(),
        ]);
    }
}
