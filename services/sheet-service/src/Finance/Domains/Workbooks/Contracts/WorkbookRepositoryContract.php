<?php

declare(strict_types=1);

namespace Finance\Domains\Workbooks\Contracts;

use Finance\Domains\Workbooks\Models\WorkbookModel;
use Illuminate\Support\Collection;

interface WorkbookRepositoryContract
{
    /**
     * @return Collection<int, WorkbookModel>
     */
    public function availableTo(string $userIdentifier): Collection;

    public function findByIdentifier(string $identifier): ?WorkbookModel;

    /**
     * @param list<string> $memberIdentifiers
     */
    public function create(string $name, string $ownerIdentifier, array $memberIdentifiers): WorkbookModel;
}
