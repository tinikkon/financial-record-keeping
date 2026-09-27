<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Data;

use Carbon\CarbonImmutable;

/**
 * Новая запись журнала: что было в ячейке и что стало.
 */
final readonly class NewCellChange
{
    public function __construct(
        public string $workbookIdentifier,
        public string $sheetIdentifier,
        public string $sheetName,
        public string $address,
        public int $row,
        public int $column,
        public ?string $valueBefore,
        public ?string $valueAfter,
        public ?string $inputBefore,
        public ?string $inputAfter,
        public int $sheetVersion,
        public string $actorIdentifier,
        public CarbonImmutable $occurredAt,
    ) {
    }
}
