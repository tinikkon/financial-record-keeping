<?php

declare(strict_types=1);

namespace Finance\Domains\Summaries\Data;

final readonly class ColumnSummary
{
    /**
     * @param string $column буквы колонки, как их видит пользователь
     */
    public function __construct(
        public string $column,
        public string $total,
        public int $filledCells,
    ) {
    }
}
