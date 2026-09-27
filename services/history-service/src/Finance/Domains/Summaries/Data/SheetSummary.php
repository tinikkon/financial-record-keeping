<?php

declare(strict_types=1);

namespace Finance\Domains\Summaries\Data;

/**
 * Сводка по одному листу: итоги колонок и то, насколько лист живой.
 */
final readonly class SheetSummary
{
    /**
     * @param list<ColumnSummary> $columns
     */
    public function __construct(
        public string $sheetIdentifier,
        public string $sheetName,
        public int $changes,
        public ?string $lastChangeAt,
        public array $columns,
    ) {
    }
}
