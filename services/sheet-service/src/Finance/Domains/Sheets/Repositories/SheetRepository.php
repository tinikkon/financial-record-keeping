<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Repositories;

use Finance\Domains\Core\Contracts\IndexDefinition;
use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Finance\Domains\Core\Repositories\AbstractMongoRepository;
use Finance\Domains\Sheets\Contracts\SheetRepositoryContract;
use Finance\Domains\Sheets\Models\SheetModel;
use Illuminate\Support\Collection;
use MongoDB\BSON\ObjectId;
use MongoDB\Operation\FindOneAndUpdate;

final class SheetRepository extends AbstractMongoRepository implements ProvidesIndexes, SheetRepositoryContract
{
    public function forWorkbook(string $workbookIdentifier): Collection
    {
        /** @var Collection<int, SheetModel> $sheets */
        $sheets = $this->query()
            ->where('workbook_id', $workbookIdentifier)
            ->orderBy('position')
            ->get();

        return $sheets;
    }

    public function findByIdentifier(string $identifier): ?SheetModel
    {
        $sheet = $this->query()->where('_id', $identifier)->first();

        return $sheet instanceof SheetModel ? $sheet : null;
    }

    public function findByName(string $workbookIdentifier, string $name): ?SheetModel
    {
        $sheet = $this->query()
            ->where('workbook_id', $workbookIdentifier)
            ->where('name', $name)
            ->first();

        return $sheet instanceof SheetModel ? $sheet : null;
    }

    public function create(
        string $workbookIdentifier,
        string $name,
        int $position,
        int $rowCount,
        int $columnCount,
        array $columnWidths = [],
    ): SheetModel {
        $sheet = new SheetModel();
        $sheet->fill([
            'workbook_id' => $workbookIdentifier,
            'name' => $name,
            'position' => $position,
            'version' => 0,
            'row_count' => $rowCount,
            'column_count' => $columnCount,
            'column_widths' => $columnWidths,
        ]);
        $sheet->save();

        return $sheet;
    }

    public function rename(string $identifier, string $name): void
    {
        $this->query()->where('_id', $identifier)->update(['name' => $name]);
    }

    public function delete(string $identifier): void
    {
        $this->query()->where('_id', $identifier)->delete();
    }

    public function updatePositions(array $positionsByIdentifier): void
    {
        foreach ($positionsByIdentifier as $identifier => $position) {
            $this->query()->where('_id', $identifier)->update(['position' => $position]);
        }
    }

    public function updateColumnWidths(string $identifier, array $columnWidths): void
    {
        $this->query()->where('_id', $identifier)->update(['column_widths' => $columnWidths]);
    }

    public function nextPosition(string $workbookIdentifier): int
    {
        $lastPosition = $this->query()
            ->where('workbook_id', $workbookIdentifier)
            ->max('position');

        return is_numeric($lastPosition) ? ((int) $lastPosition) + 1 : 0;
    }

    public function incrementVersion(string $identifier): int
    {
        $updated = $this->collection()->findOneAndUpdate(
            ['_id' => new ObjectId($identifier)],
            ['$inc' => ['version' => 1]],
            [
                'returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER,
                'projection' => ['version' => 1],
                'typeMap' => ['root' => 'array'],
                ...$this->sessionOptions(),
            ],
        );

        return is_array($updated) ? (int) $updated['version'] : 0;
    }

    public function collectionName(): string
    {
        return 'sheets';
    }

    public function indexes(): array
    {
        return [
            new IndexDefinition(name: 'sheets_workbook_position', keys: ['workbook_id' => 1, 'position' => 1]),
            new IndexDefinition(
                name: 'sheets_workbook_name_unique',
                keys: ['workbook_id' => 1, 'name' => 1],
                unique: true,
            ),
        ];
    }

    protected function modelClass(): string
    {
        return SheetModel::class;
    }
}
