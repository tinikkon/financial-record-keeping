<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Repositories;

use Finance\Domains\Changelog\Data\CellStateUpdate;
use Finance\Domains\Changelog\Models\CellStateModel;
use Finance\Domains\Core\Contracts\IndexDefinition;
use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Finance\Domains\Core\Repositories\AbstractMongoRepository;
use MongoDB\BSON\Decimal128;

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

    /**
     * Записывает состояние ячейки, создавая запись при первом изменении.
     *
     * Операция выполняется одним запросом с upsert, а не чтением и сохранением
     * модели: Eloquent при обновлении отправляет только поля, которые считает
     * изменившимися, и поле с собственным приведением типа в этот список
     * не попадает — значение молча остаётся прежним.
     */
    public function remember(CellStateUpdate $state): void
    {
        $position = ['sheet_id' => $state->sheetIdentifier, 'address' => $state->address];

        $this->collection()->updateOne(
            $position,
            ['$set' => [
                ...$position,
                'workbook_id' => $state->workbookIdentifier,
                'sheet_name' => $state->sheetName,
                'row' => $state->row,
                'column' => $state->column,
                'value' => $state->value,
                'value_number' => $state->numberValue === null ? null : new Decimal128($state->numberValue),
                'input' => $state->input,
            ]],
            ['upsert' => true, ...$this->sessionOptions()],
        );
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
            // Сводка по книге отбирает состояния одним запросом, до группировки:
            // отбор первой ступенью конвейера отсекает лишнее заранее.
            new IndexDefinition(name: 'cell_states_workbook', keys: ['workbook_id' => 1, 'sheet_id' => 1]),
        ];
    }

    protected function modelClass(): string
    {
        return CellStateModel::class;
    }
}
