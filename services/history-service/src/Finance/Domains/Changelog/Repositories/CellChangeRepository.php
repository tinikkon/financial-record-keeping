<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Repositories;

use Finance\Domains\Changelog\Data\NewCellChange;
use Finance\Domains\Changelog\Models\CellChangeModel;
use Finance\Domains\Core\Contracts\IndexDefinition;
use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Finance\Domains\Core\Repositories\AbstractMongoRepository;
use Illuminate\Support\Collection;

final class CellChangeRepository extends AbstractMongoRepository implements ProvidesIndexes
{
    public function record(NewCellChange $change): CellChangeModel
    {
        $model = new CellChangeModel();
        $model->fill([
            'workbook_id' => $change->workbookIdentifier,
            'sheet_id' => $change->sheetIdentifier,
            'sheet_name' => $change->sheetName,
            'address' => $change->address,
            'row' => $change->row,
            'column' => $change->column,
            'value_before' => $change->valueBefore,
            'value_after' => $change->valueAfter,
            'input_before' => $change->inputBefore,
            'input_after' => $change->inputAfter,
            'sheet_version' => $change->sheetVersion,
            'actor_id' => $change->actorIdentifier,
            'occurred_at' => $change->occurredAt,
        ]);
        $model->save();

        return $model;
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

    /**
     * Все правки листа до указанной версии включительно, в порядке применения.
     *
     * @return Collection<int, CellChangeModel>
     */
    public function upToVersion(string $sheetIdentifier, int $version): Collection
    {
        /** @var Collection<int, CellChangeModel> $changes */
        $changes = $this->query()
            ->where('sheet_id', $sheetIdentifier)
            ->where('sheet_version', '<=', $version)
            ->orderBy('sheet_version')
            ->orderBy('occurred_at')
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
