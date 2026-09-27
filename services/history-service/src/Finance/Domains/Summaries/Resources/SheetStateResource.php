<?php

declare(strict_types=1);

namespace Finance\Domains\Summaries\Resources;

use Finance\Domains\Summaries\Data\CellSnapshot;
use Finance\Domains\Summaries\Data\SheetState;

final readonly class SheetStateResource
{
    /**
     * @return array<string, array{value: string|null, input: string|null}>
     */
    public static function toArray(SheetState $state): array
    {
        return array_map(
            static fn (CellSnapshot $cell): array => ['value' => $cell->value, 'input' => $cell->input],
            $state->cellsByAddress,
        );
    }
}
