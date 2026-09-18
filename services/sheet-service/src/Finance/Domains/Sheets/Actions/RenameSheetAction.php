<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Actions;

use Finance\Domains\Sheets\Contracts\SheetRepositoryContract;
use Finance\Domains\Sheets\Exceptions\SheetNameAlreadyUsedException;
use Finance\Domains\Sheets\Models\SheetModel;

final readonly class RenameSheetAction
{
    public function __construct(private SheetRepositoryContract $sheets)
    {
    }

    /**
     * @throws SheetNameAlreadyUsedException
     */
    public function execute(SheetModel $sheet, string $name): void
    {
        $existing = $this->sheets->findByName($sheet->workbook_id, $name);

        if ($existing !== null && $existing->identifier() !== $sheet->identifier()) {
            throw new SheetNameAlreadyUsedException($name);
        }

        $this->sheets->rename($sheet->identifier(), $name);
    }
}
