<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Repositories;

use Finance\Domains\Cells\Contracts\CellRepositoryContract;
use Finance\Domains\Cells\Data\CellContent;
use Finance\Domains\Cells\Data\CellFormat;
use Finance\Domains\Cells\Enums\CellKind;
use Finance\Domains\Cells\Exceptions\CellNotSavedException;
use Finance\Domains\Cells\Models\CellModel;
use Finance\Domains\Core\Contracts\IndexDefinition;
use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Finance\Domains\Core\Repositories\AbstractMongoRepository;
use Finance\FormulaEngine\Values\CellRange;
use Finance\FormulaEngine\Values\CellReference;
use Finance\FormulaEngine\Values\CellValue;
use Illuminate\Support\Collection;
use MongoDB\BSON\Decimal128;

final class CellRepository extends AbstractMongoRepository implements CellRepositoryContract, ProvidesIndexes
{
    public function forSheet(string $sheetIdentifier): Collection
    {
        /** @var Collection<int, CellModel> $cells */
        $cells = $this->query()
            ->where('sheet_id', $sheetIdentifier)
            ->orderBy('row')
            ->orderBy('column')
            ->get();

        return $cells;
    }

    public function findAt(string $sheetIdentifier, CellReference $reference): ?CellModel
    {
        $cell = $this->query()
            ->where('sheet_id', $sheetIdentifier)
            ->where('row', $reference->row)
            ->where('column', $reference->column)
            ->first();

        return $cell instanceof CellModel ? $cell : null;
    }

    public function findInRange(string $sheetIdentifier, CellRange $range): Collection
    {
        /** @var Collection<int, CellModel> $cells */
        $cells = $this->query()
            ->where('sheet_id', $sheetIdentifier)
            ->whereBetween('row', [$range->minimumRow, $range->maximumRow])
            ->whereBetween('column', [$range->minimumColumn, $range->maximumColumn])
            ->get();

        return $cells;
    }

    public function dependentsOf(string $sheetIdentifier, CellReference $reference): Collection
    {
        // Запрос описан напрямую языком MongoDB: условие по диапазонам проверяет
        // элементы массива границ, и выразить его построителем Eloquent нельзя.
        $filter = [
            'sheet_id' => $sheetIdentifier,
            '$or' => [
                ['depends_on_cells' => $reference->key()],
                ['depends_on_ranges' => ['$elemMatch' => [
                    'min_row' => ['$lte' => $reference->row],
                    'max_row' => ['$gte' => $reference->row],
                    'min_column' => ['$lte' => $reference->column],
                    'max_column' => ['$gte' => $reference->column],
                ]]],
            ],
        ];

        /** @var Collection<int, CellModel> $cells */
        // @phpstan-ignore argument.type (построитель MongoDB принимает фильтр массивом, базовая подпись описана строкой)
        $cells = $this->query()->whereRaw($filter)->get();

        return $cells;
    }

    /**
     * @throws CellNotSavedException
     */
    public function saveContent(
        string $sheetIdentifier,
        CellReference $reference,
        CellContent $content,
        string $updatedBy,
    ): CellModel {
        return $this->upsert($sheetIdentifier, $reference, [
            'input' => $content->input,
            'kind' => $content->kind->value,
            'value_number' => $this->decimalOrNull($content->numberValue),
            'value_text' => $content->textValue,
            'error' => null,
            'depends_on_cells' => $content->dependsOnCells,
            'depends_on_ranges' => array_map(
                static fn (CellRange $range): array => [
                    'min_row' => $range->minimumRow,
                    'max_row' => $range->maximumRow,
                    'min_column' => $range->minimumColumn,
                    'max_column' => $range->maximumColumn,
                ],
                $content->dependsOnRanges,
            ),
            'updated_by' => $updatedBy,
        ]);
    }

    /**
     * @throws CellNotSavedException
     */
    public function saveComputedValue(
        string $sheetIdentifier,
        CellReference $reference,
        CellValue $value,
        string $updatedBy,
    ): CellModel {
        return $this->upsert($sheetIdentifier, $reference, [
            'value_number' => $this->decimalOrNull($value->isNumber() ? (string) $value->numberValue() : null),
            'value_text' => $value->isText() ? $value->textValue() : null,
            'error' => $value->errorValue()?->value,
            'updated_by' => $updatedBy,
        ]);
    }

    /**
     * Пустое оформление хранится как null, а не как пустой массив: пустой
     * массив ушёл бы клиенту списком вместо объекта.
     *
     * @throws CellNotSavedException
     */
    public function saveFormat(string $sheetIdentifier, CellReference $reference, CellFormat $format): CellModel
    {
        return $this->upsert(
            $sheetIdentifier,
            $reference,
            ['format' => $format->isEmpty() ? null : $format->toArray()],
            // Ячейка, заведённая ради одного оформления, пуста по содержимому.
            ['kind' => CellKind::Empty->value],
        );
    }

    public function deleteForSheet(string $sheetIdentifier): void
    {
        $this->query()->where('sheet_id', $sheetIdentifier)->delete();
    }

    public function collectionName(): string
    {
        return 'cells';
    }

    public function indexes(): array
    {
        return [
            new IndexDefinition(
                name: 'cells_sheet_position_unique',
                keys: ['sheet_id' => 1, 'row' => 1, 'column' => 1],
                unique: true,
            ),
            // Обратный поиск: кто ссылается на эту ячейку. Поле хранит массив
            // адресов, и многоключевой индекс заводит запись на каждый элемент.
            new IndexDefinition(name: 'cells_dependencies', keys: ['sheet_id' => 1, 'depends_on_cells' => 1]),
            new IndexDefinition(name: 'cells_dependency_ranges', keys: [
                'sheet_id' => 1,
                'depends_on_ranges.min_row' => 1,
                'depends_on_ranges.max_row' => 1,
            ]),
        ];
    }

    protected function modelClass(): string
    {
        return CellModel::class;
    }

    /**
     * Запись одним запросом с upsert, а не чтением и сохранением модели.
     * Eloquent при обновлении отправляет только поля, которые считает
     * изменившимися, и поле с собственным приведением типа в этот список
     * не попадает: в памяти новое значение есть, в базе остаётся прежнее.
     * Поймать такое тестом можно только перечитав запись заново.
     *
     * @param array<string, mixed> $fields       поля документа в том виде, в каком их хранит база
     * @param array<string, mixed> $insertFields поля, которые задаются только при создании
     *
     * @throws CellNotSavedException
     */
    private function upsert(
        string $sheetIdentifier,
        CellReference $reference,
        array $fields,
        array $insertFields = [],
    ): CellModel {
        $position = [
            'sheet_id' => $sheetIdentifier,
            'row' => $reference->row,
            'column' => $reference->column,
        ];

        $update = [
            '$set' => [...$fields, ...$position],
            '$currentDate' => ['updated_at' => true],
        ];
        if ($insertFields !== []) {
            $update['$setOnInsert'] = $insertFields;
        }

        $this->collection()->updateOne($position, $update, ['upsert' => true, ...$this->sessionOptions()]);

        $saved = $this->findAt($sheetIdentifier, $reference);
        if ($saved === null) {
            throw new CellNotSavedException($reference->key());
        }

        return $saved;
    }

    private function decimalOrNull(?string $number): ?Decimal128
    {
        return $number === null ? null : new Decimal128($number);
    }
}
