<?php

declare(strict_types=1);

namespace Finance\Domains\Summaries\Repositories;

use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;

/**
 * Сводки по книге.
 *
 * Считаются конвейером агрегации на стороне базы, а не выборкой всего в память:
 * суммирование по тысячам ячеек — работа базы, и делать её в PHP значило бы
 * тащить через сеть всё, чтобы выбросить почти всё.
 */
final readonly class CellStateSummaryRepository
{
    /**
     * Итоги по каждой колонке каждого листа.
     *
     * @return list<array{sheetId: string, sheetName: string, column: int, total: string, filledCells: int}>
     */
    public function columnTotals(string $workbookIdentifier): array
    {
        $pipeline = [
            // Отбор идёт первой ступенью: он отсекает лишнее до группировки
            // и может опереться на индекс, тогда как последующие ступени — нет.
            ['$match' => [
                'workbook_id' => $workbookIdentifier,
                'value_number' => ['$ne' => null],
            ]],
            ['$group' => [
                '_id' => [
                    'sheetId' => '$sheet_id',
                    'sheetName' => '$sheet_name',
                    'column' => '$column',
                ],
                'total' => ['$sum' => '$value_number'],
                'filledCells' => ['$sum' => 1],
            ]],
            ['$sort' => ['_id.sheetName' => 1, '_id.column' => 1]],
        ];

        /** @var Connection $connection */
        $connection = DB::connection('mongodb');

        $rows = $connection->getCollection('cell_states')->aggregate(
            $pipeline,
            ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']],
        );

        $totals = [];

        foreach ($rows as $row) {
            /** @var array{_id: array{sheetId: string, sheetName: string, column: int}, total: mixed, filledCells: int} $row */
            $totals[] = [
                'sheetId' => (string) $row['_id']['sheetId'],
                'sheetName' => (string) $row['_id']['sheetName'],
                'column' => (int) $row['_id']['column'],
                'total' => (string) $row['total'],
                'filledCells' => (int) $row['filledCells'],
            ];
        }

        return $totals;
    }

    /**
     * Сколько правок пришлось на каждый лист и когда лист трогали в последний раз.
     *
     * @return array<string, array{changes: int, lastChangeAt: string}>
     */
    public function activityBySheet(string $workbookIdentifier): array
    {
        $pipeline = [
            ['$match' => ['workbook_id' => $workbookIdentifier]],
            ['$group' => [
                '_id' => '$sheet_id',
                'changes' => ['$sum' => 1],
                'lastChangeAt' => ['$max' => '$occurred_at'],
            ]],
        ];

        /** @var Connection $connection */
        $connection = DB::connection('mongodb');

        $rows = $connection->getCollection('cell_changes')->aggregate(
            $pipeline,
            ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']],
        );

        $activity = [];

        foreach ($rows as $row) {
            /** @var array{_id: string, changes: int, lastChangeAt: mixed} $row */
            $activity[(string) $row['_id']] = [
                'changes' => (int) $row['changes'],
                'lastChangeAt' => (string) $row['lastChangeAt'],
            ];
        }

        return $activity;
    }
}
