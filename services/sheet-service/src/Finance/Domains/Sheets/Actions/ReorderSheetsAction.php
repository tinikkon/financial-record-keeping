<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Actions;

use Finance\Domains\Sheets\Contracts\SheetRepositoryContract;
use Finance\Domains\Sheets\Models\SheetModel;

/**
 * Расставляет вкладки в заданном порядке.
 *
 * Листы, не попавшие в присланный список, остаются позади в прежнем порядке —
 * так перетаскивание одной вкладки не требует присылать все остальные.
 */
final readonly class ReorderSheetsAction
{
    public function __construct(private SheetRepositoryContract $sheets)
    {
    }

    /**
     * @param list<string> $orderedIdentifiers
     */
    public function execute(string $workbookIdentifier, array $orderedIdentifiers): void
    {
        $existingIdentifiers = $this->sheets->forWorkbook($workbookIdentifier)
            ->map(static fn (SheetModel $sheet): string => $sheet->identifier())
            ->all();

        $requested = array_values(array_intersect($orderedIdentifiers, $existingIdentifiers));
        $remaining = array_values(array_diff($existingIdentifiers, $requested));

        $positions = [];

        foreach ([...$requested, ...$remaining] as $position => $identifier) {
            $positions[$identifier] = $position;
        }

        $this->sheets->updatePositions($positions);
    }
}
