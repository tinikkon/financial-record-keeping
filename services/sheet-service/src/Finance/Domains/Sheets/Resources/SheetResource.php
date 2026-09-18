<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Resources;

use Finance\Domains\Sheets\Models\SheetModel;

final readonly class SheetResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(SheetModel $sheet): array
    {
        return [
            'id' => $sheet->identifier(),
            'workbookId' => $sheet->workbook_id,
            'name' => $sheet->name,
            'position' => $sheet->position,
            'version' => $sheet->version,
            'rowCount' => $sheet->row_count,
            'columnCount' => $sheet->column_count,
            'columnWidths' => $sheet->column_widths ?? [],
        ];
    }
}
