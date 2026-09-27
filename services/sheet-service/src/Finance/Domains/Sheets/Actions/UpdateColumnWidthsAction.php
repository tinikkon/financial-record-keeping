<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Actions;

use Finance\Domains\Sheets\Contracts\SheetRepositoryContract;
use Finance\Domains\Sheets\Data\ColumnWidths;
use Finance\Domains\Sheets\Models\SheetModel;

final readonly class UpdateColumnWidthsAction
{
    private const int MINIMUM_WIDTH = 40;

    private const int MAXIMUM_WIDTH = 600;

    public function __construct(private SheetRepositoryContract $sheets)
    {
    }

    public function execute(SheetModel $sheet, ColumnWidths $changes): void
    {
        $merged = $sheet->columnWidths()->merge($changes->clamped(self::MINIMUM_WIDTH, self::MAXIMUM_WIDTH));

        $this->sheets->updateColumnWidths($sheet->identifier(), $merged);
    }
}
