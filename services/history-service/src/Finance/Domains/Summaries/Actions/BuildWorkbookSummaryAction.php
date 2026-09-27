<?php

declare(strict_types=1);

namespace Finance\Domains\Summaries\Actions;

use Finance\Domains\Core\Support\ColumnLetters;
use Finance\Domains\Summaries\Data\ColumnSummary;
use Finance\Domains\Summaries\Data\SheetSummary;
use Finance\Domains\Summaries\Repositories\CellStateSummaryRepository;

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
     * @return list<SheetSummary>
     */
    public function execute(string $workbookIdentifier): array
    {
        $activity = $this->summaries->activityBySheet($workbookIdentifier);

        // Итоги приходят плоским списком, уже упорядоченным по листу и колонке;
        // здесь они только раскладываются по листам.
        $namesBySheet = [];
        $columnsBySheet = [];
        foreach ($this->summaries->columnTotals($workbookIdentifier) as $total) {
            $namesBySheet[$total->sheetIdentifier] ??= $total->sheetName;
            $columnsBySheet[$total->sheetIdentifier][] = new ColumnSummary(
                column: ColumnLetters::fromNumber($total->column),
                total: $total->total,
                filledCells: $total->filledCells,
            );
        }

        $summaries = [];
        foreach ($columnsBySheet as $sheetIdentifier => $columns) {
            $summaries[] = new SheetSummary(
                sheetIdentifier: (string) $sheetIdentifier,
                sheetName: $namesBySheet[$sheetIdentifier],
                changes: $activity[$sheetIdentifier]->changes ?? 0,
                lastChangeAt: $activity[$sheetIdentifier]->lastChangeAt ?? null,
                columns: $columns,
            );
        }

        return $summaries;
    }
}
