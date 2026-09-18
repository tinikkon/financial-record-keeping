<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Repositories;

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
     *
     * @param array<string, mixed> $attributes
     */
    public function remember(string $sheetIdentifier, string $address, array $attributes): void
    {
        $number = $attributes['value_number'] ?? null;
        $attributes['value_number'] = $number === null ? null : new Decimal128((string) $number);

        $this->collection()->updateOne(
            ['sheet_id' => $sheetIdentifier, 'address' => $address],
            ['$set' => [...$attributes, 'sheet_id' => $sheetIdentifier, 'address' => $address]],
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
