<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Actions;

use Finance\Domains\Cells\Contracts\CellRepositoryContract;
use Finance\Domains\Sheets\Contracts\SheetRepositoryContract;
use Finance\Domains\Sheets\Models\SheetModel;

/**
 * Удаляет лист вместе с его ячейками.
 *
 * Ячейки лежат отдельной коллекцией, и база сама их за листом не уберёт —
 * связей между коллекциями в MongoDB нет.
 */
final readonly class DeleteSheetAction
{
    public function __construct(
        private SheetRepositoryContract $sheets,
        private CellRepositoryContract $cells,
    ) {
    }

    public function execute(SheetModel $sheet): void
    {
        $this->cells->deleteForSheet($sheet->identifier());
        $this->sheets->delete($sheet->identifier());
    }
}
