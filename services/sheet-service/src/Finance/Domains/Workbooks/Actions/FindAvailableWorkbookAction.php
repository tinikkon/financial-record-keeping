<?php

declare(strict_types=1);

namespace Finance\Domains\Workbooks\Actions;

use Finance\Domains\Workbooks\Contracts\WorkbookRepositoryContract;
use Finance\Domains\Workbooks\Exceptions\WorkbookNotAvailableException;
use Finance\Domains\Workbooks\Models\WorkbookModel;

/**
 * Находит книгу и сразу проверяет доступ к ней.
 *
 * Отдельным действием, потому что этой проверкой начинается почти каждый
 * сценарий работы с листами и ячейками.
 */
final readonly class FindAvailableWorkbookAction
{
    public function __construct(private WorkbookRepositoryContract $workbooks)
    {
    }

    /**
     * @throws WorkbookNotAvailableException
     */
    public function execute(string $workbookIdentifier, string $userIdentifier): WorkbookModel
    {
        $workbook = $this->workbooks->findByIdentifier($workbookIdentifier);

        if ($workbook === null || ! $workbook->isAvailableTo($userIdentifier)) {
            throw new WorkbookNotAvailableException();
        }

        return $workbook;
    }
}
