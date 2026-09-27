<?php

declare(strict_types=1);

namespace Finance\Domains\Summaries\Data;

/**
 * Что лежало в ячейке на какую-то версию: введённое и показанное.
 */
final readonly class CellSnapshot
{
    public function __construct(
        public ?string $value,
        public ?string $input,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->value === null && $this->input === null;
    }
}
