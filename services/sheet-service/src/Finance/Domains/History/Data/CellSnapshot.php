<?php

declare(strict_types=1);

namespace Finance\Domains\History\Data;

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
}
