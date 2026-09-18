<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Repositories;

use Finance\Domains\Changelog\Models\CellChangeModel;
use Finance\Domains\Core\Contracts\IndexDefinition;
use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Finance\Domains\Core\Repositories\AbstractMongoRepository;
use Illuminate\Support\Collection;

final class CellChangeRepository extends AbstractMongoRepository implements ProvidesIndexes
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function record(array $attributes): CellChangeModel
    {
        $change = new CellChangeModel();
        $change->fill($attributes);
        $change->save();

        return $change;
    }

    /**
     * Порядок задаётся двумя признаками: метка времени имеет точность до секунды,
     * и две правки в одну секунду без номера версии легли бы как придётся.
     *
     * @return Collection<int, CellChangeModel>
     */
    public function forSheet(string $sheetIdentifier, int $limit = 200): Collection
    {
        /** @var Collection<int, CellChangeModel> $changes */
        $changes = $this->query()
            ->where('sheet_id', $sheetIdentifier)
            ->orderBy('occurred_at', 'desc')
            ->orderBy('sheet_version', 'desc')
            ->limit($limit)
            ->get();

        return $changes;
    }

    /**
     * @return Collection<int, CellChangeModel>
     */
    public function forCell(string $sheetIdentifier, string $address, int $limit = 100): Collection
    {
        /** @var Collection<int, CellChangeModel> $changes */
        $changes = $this->query()
            ->where('sheet_id', $sheetIdentifier)
            ->where('address', $address)
            ->orderBy('occurred_at', 'desc')
            ->orderBy('sheet_version', 'desc')
            ->limit($limit)
            ->get();

        return $changes;
    }

    public function collectionName(): string
    {
        return 'cell_changes';
    }

    public function indexes(): array
    {
        return [
            new IndexDefinition(name: 'cell_changes_sheet_time', keys: ['sheet_id' => 1, 'occurred_at' => -1]),
            new IndexDefinition(name: 'cell_changes_cell_time', keys: ['sheet_id' => 1, 'address' => 1, 'occurred_at' => -1]),
            new IndexDefinition(name: 'cell_changes_version', keys: ['sheet_id' => 1, 'sheet_version' => -1]),
        ];
    }

    protected function modelClass(): string
    {
        return CellChangeModel::class;
    }
}
