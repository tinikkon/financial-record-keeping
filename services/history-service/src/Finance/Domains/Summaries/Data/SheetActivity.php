<?php

declare(strict_types=1);

namespace Finance\Domains\Summaries\Data;

/**
 * Сколько раз правили лист и когда в последний раз.
 */
final readonly class SheetActivity
{
    public function __construct(
        public int $changes,
        public string $lastChangeAt,
    ) {
    }
}
