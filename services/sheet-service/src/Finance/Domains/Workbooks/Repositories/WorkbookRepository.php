<?php

declare(strict_types=1);

namespace Finance\Domains\Workbooks\Repositories;

use Finance\Domains\Core\Contracts\IndexDefinition;
use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Finance\Domains\Core\Repositories\AbstractMongoRepository;
use Finance\Domains\Workbooks\Contracts\WorkbookRepositoryContract;
use Finance\Domains\Workbooks\Models\WorkbookModel;
use Illuminate\Support\Collection;

final class WorkbookRepository extends AbstractMongoRepository implements ProvidesIndexes, WorkbookRepositoryContract
{
    public function availableTo(string $userIdentifier): Collection
    {
        /** @var Collection<int, WorkbookModel> $workbooks */
        $workbooks = $this->query()
            ->where(static function ($query) use ($userIdentifier): void {
                $query->where('owner_id', $userIdentifier)
                    ->orWhere('member_ids', $userIdentifier);
            })
            ->orderBy('created_at')
            ->get();

        return $workbooks;
    }

    public function findByIdentifier(string $identifier): ?WorkbookModel
    {
        $workbook = $this->query()->where('_id', $identifier)->first();

        return $workbook instanceof WorkbookModel ? $workbook : null;
    }

    public function create(string $name, string $ownerIdentifier, array $memberIdentifiers): WorkbookModel
    {
        $workbook = new WorkbookModel();
        $workbook->fill([
            'name' => $name,
            'owner_id' => $ownerIdentifier,
            'member_ids' => $memberIdentifiers,
        ]);
        $workbook->save();

        return $workbook;
    }

    public function collectionName(): string
    {
        return 'workbooks';
    }

    public function indexes(): array
    {
        return [
            new IndexDefinition(name: 'workbooks_owner', keys: ['owner_id' => 1]),
            // Многоключевой индекс: поле хранит массив, и MongoDB заводит запись
            // на каждый его элемент, поэтому поиск по участнику идёт по индексу.
            new IndexDefinition(name: 'workbooks_members', keys: ['member_ids' => 1]),
        ];
    }

    protected function modelClass(): string
    {
        return WorkbookModel::class;
    }
}
