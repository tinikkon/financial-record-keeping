<?php

declare(strict_types=1);

namespace Finance\Domains\Workbooks\Controllers;

use Finance\Domains\Auth\Exceptions\InvalidAccessTokenException;
use Finance\Domains\Auth\Support\CurrentUser;
use Finance\Domains\Workbooks\Actions\CreateWorkbookAction;
use Finance\Domains\Workbooks\Contracts\WorkbookRepositoryContract;
use Finance\Domains\Workbooks\Models\WorkbookModel;
use Finance\Domains\Workbooks\Requests\CreateWorkbookRequest;
use Finance\Domains\Workbooks\Resources\WorkbookResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class WorkbookController
{
    /**
     * @throws InvalidAccessTokenException
     */
    public function index(Request $request, WorkbookRepositoryContract $workbooks): JsonResponse
    {
        $available = $workbooks->availableTo(CurrentUser::identifier($request))
            ->map(static fn (WorkbookModel $workbook): array => WorkbookResource::toArray($workbook))
            ->values()
            ->all();

        return new JsonResponse(['workbooks' => $available]);
    }

    /**
     * @throws InvalidAccessTokenException
     */
    public function store(CreateWorkbookRequest $request, CreateWorkbookAction $createWorkbook): JsonResponse
    {
        $workbook = $createWorkbook->execute($request->name(), CurrentUser::identifier($request));

        return new JsonResponse(WorkbookResource::toArray($workbook), JsonResponse::HTTP_CREATED);
    }
}
