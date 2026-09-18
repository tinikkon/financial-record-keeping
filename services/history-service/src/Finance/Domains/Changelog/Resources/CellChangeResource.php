<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Resources;

use Finance\Domains\Changelog\Models\CellChangeModel;

final readonly class CellChangeResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(CellChangeModel $change): array
    {
        return [
            'id' => (string) $change->_id,
            'sheetId' => $change->sheet_id,
            'sheetName' => $change->sheet_name,
            'address' => $change->address,
            'valueBefore' => $change->value_before,
            'valueAfter' => $change->value_after,
            'inputBefore' => $change->input_before,
            'inputAfter' => $change->input_after,
            'sheetVersion' => $change->sheet_version,
            'actorId' => $change->actor_id,
            'occurredAt' => $change->occurred_at->toIso8601String(),
        ];
    }
}
