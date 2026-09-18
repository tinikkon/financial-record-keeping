<?php

declare(strict_types=1);

namespace Finance\Domains\Workbooks\Resources;

use Finance\Domains\Workbooks\Models\WorkbookModel;

final readonly class WorkbookResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(WorkbookModel $workbook): array
    {
        return [
            'id' => $workbook->identifier(),
            'name' => $workbook->name,
            'ownerId' => $workbook->owner_id,
            'memberIds' => $workbook->member_ids ?? [],
        ];
    }
}
