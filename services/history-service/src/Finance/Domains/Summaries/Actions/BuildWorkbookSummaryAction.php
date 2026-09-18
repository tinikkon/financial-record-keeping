<?php

declare(strict_types=1);

namespace Finance\Domains\Summaries\Actions;

use Finance\Domains\Summaries\Repositories\CellStateSummaryRepository;
use Finance\Domains\Core\Support\ColumnLetters;

/**
 * Собирает сводку по книге: по каждому листу — итоги колонок и число правок.
 *
 * Приложение не знает, что означает колонка: смысл ей придаёт заголовок,
 * вписанный пользователем. Поэтому сводка честно показывает итог по каждой
 * колонке, а не выдумывает «доходы» и «расходы».
 */
final readonly class BuildWorkbookSummaryAction
{
    public function __construct(private CellStateSummaryRepository $summaries)
    {
    }

    /**
     * @return list<array{sheetId: string, sheetName: string, changes: int, lastChangeAt: string|null, columns: list<array{column: string, total: string, filledCells: int}>}>
     */
    public function execute(string $workbookIdentifier): array
    {
        $activity = $this->summaries->activityBySheet($workbookIdentifier);
        $bySheet = [];

        foreach ($this->summaries->columnTotals($workbookIdentifier) as $total) {
            $bySheet[$total['sheetId']] ??= [
                'sheetId' => $total['sheetId'],
                'sheetName' => $total['sheetName'],
                'changes' => $activity[$total['sheetId']]['changes'] ?? 0,
                'lastChangeAt' => $activity[$total['sheetId']]['lastChangeAt'] ?? null,
                'columns' => [],
            ];

            $bySheet[$total['sheetId']]['columns'][] = [
                'column' => ColumnLetters::fromNumber($total['column']),
                'total' => $total['total'],
                'filledCells' => $total['filledCells'],
            ];
        }

        return array_values($bySheet);
    }
}
