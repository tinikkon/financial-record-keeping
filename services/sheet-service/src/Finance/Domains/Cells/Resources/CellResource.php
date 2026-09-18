<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Resources;

use Finance\Domains\Cells\Models\CellModel;

final readonly class CellResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(CellModel $cell): array
    {
        return [
            'address' => $cell->address(),
            'row' => $cell->row,
            'column' => $cell->column,
            'input' => $cell->input,
            'kind' => $cell->kind,
            'value' => $cell->value_number !== null ? (string) $cell->value_number->strippedOfTrailingZeros() : $cell->value_text,
            'error' => $cell->error,
            'format' => $cell->format ?? new \stdClass(),
        ];
    }
}
