<?php

declare(strict_types=1);

namespace Finance\Domains\Summaries\Resources;

use Finance\Domains\Summaries\Data\ColumnSummary;
use Finance\Domains\Summaries\Data\SheetSummary;

final readonly class SheetSummaryResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(SheetSummary $summary): array
    {
        return [
            'sheetId' => $summary->sheetIdentifier,
            'sheetName' => $summary->sheetName,
            'changes' => $summary->changes,
            'lastChangeAt' => $summary->lastChangeAt,
            'columns' => array_map(
                static fn (ColumnSummary $column): array => [
                    'column' => $column->column,
                    'total' => $column->total,
                    'filledCells' => $column->filledCells,
                ],
                $summary->columns,
            ),
        ];
    }
}
