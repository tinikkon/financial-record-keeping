<?php

declare(strict_types=1);

namespace Finance\Domains\Workbooks\Actions;

use Finance\Domains\Workbooks\Contracts\WorkbookRepositoryContract;
use Finance\Domains\Workbooks\Models\WorkbookModel;

final readonly class CreateWorkbookAction
{
    public function __construct(private WorkbookRepositoryContract $workbooks)
    {
    }

    /**
     * @param list<string> $memberIdentifiers
     */
    public function execute(string $name, string $ownerIdentifier, array $memberIdentifiers = []): WorkbookModel
    {
        return $this->workbooks->create($name, $ownerIdentifier, $memberIdentifiers);
    }
}
