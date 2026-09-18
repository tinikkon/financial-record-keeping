<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Repositories;

use Finance\Domains\Changelog\Models\CellStateModel;
use Finance\Domains\Core\Contracts\IndexDefinition;
use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Finance\Domains\Core\Repositories\AbstractMongoRepository;

final class CellStateRepository extends AbstractMongoRepository implements ProvidesIndexes
{
    public function find(string $sheetIdentifier, string $address): ?CellStateModel
    {
        $state = $this->query()
            ->where('sheet_id', $sheetIdentifier)
            ->where('address', $address)
            ->first();

        return $state instanceof CellStateModel ? $state : null;
    }

    public function remember(string $sheetIdentifier, string $address, ?string $value, ?string $input): void
    {
        $state = $this->find($sheetIdentifier, $address) ?? new CellStateModel();
        $state->fill([
            'sheet_id' => $sheetIdentifier,
            'address' => $address,
            'value' => $value,
            'input' => $input,
        ]);
        $state->save();
    }

    public function collectionName(): string
    {
        return 'cell_states';
    }

    public function indexes(): array
    {
        return [
            new IndexDefinition(
                name: 'cell_states_position_unique',
                keys: ['sheet_id' => 1, 'address' => 1],
                unique: true,
            ),
        ];
    }

    protected function modelClass(): string
    {
        return CellStateModel::class;
    }
}
