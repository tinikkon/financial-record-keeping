<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Actions;

use Finance\Domains\Sheets\Contracts\SheetRepositoryContract;
use Finance\Domains\Sheets\Exceptions\SheetNameAlreadyUsedException;
use Finance\Domains\Sheets\Models\SheetModel;

final readonly class CreateSheetAction
{
    public function __construct(private SheetRepositoryContract $sheets)
    {
    }

    /**
     * @throws SheetNameAlreadyUsedException
     */
    public function execute(string $workbookIdentifier, string $name): SheetModel
    {
        if ($this->sheets->findByName($workbookIdentifier, $name) !== null) {
            throw new SheetNameAlreadyUsedException($name);
        }

        return $this->sheets->create(
            workbookIdentifier: $workbookIdentifier,
            name: $name,
            position: $this->sheets->nextPosition($workbookIdentifier),
            rowCount: SheetModel::DEFAULT_ROW_COUNT,
            columnCount: SheetModel::DEFAULT_COLUMN_COUNT,
        );
    }
}
