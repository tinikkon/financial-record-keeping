<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Actions;

use Finance\Domains\Sheets\Contracts\SheetRepositoryContract;
use Finance\Domains\Sheets\Models\SheetModel;

final readonly class UpdateColumnWidthsAction
{
    private const int MINIMUM_WIDTH = 40;

    private const int MAXIMUM_WIDTH = 600;

    public function __construct(private SheetRepositoryContract $sheets)
    {
    }

    /**
     * @param array<string, int> $columnWidths ширины по буквам колонок
     */
    public function execute(SheetModel $sheet, array $columnWidths): void
    {
        $merged = $sheet->column_widths ?? [];

        foreach ($columnWidths as $column => $width) {
            $merged[$column] = max(self::MINIMUM_WIDTH, min(self::MAXIMUM_WIDTH, $width));
        }

        $this->sheets->updateColumnWidths($sheet->identifier(), $merged);
    }
}
