<?php

declare(strict_types=1);

namespace Finance\Domains\Summaries\Data;

/**
 * Итог одной колонки одного листа, посчитанный базой.
 */
final readonly class ColumnTotal
{
    /**
     * @param string $total сумма строкой: десятичное число без потери точности
     */
    public function __construct(
        public string $sheetIdentifier,
        public string $sheetName,
        public int $column,
        public string $total,
        public int $filledCells,
    ) {
    }
}
