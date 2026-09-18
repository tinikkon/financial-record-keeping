<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Actions;

use Finance\Domains\Sheets\Contracts\SheetRepositoryContract;
use Finance\Domains\Sheets\Exceptions\SheetNotAvailableException;
use Finance\Domains\Sheets\Models\SheetModel;
use Finance\Domains\Workbooks\Actions\FindAvailableWorkbookAction;
use Finance\Domains\Workbooks\Exceptions\WorkbookNotAvailableException;

/**
 * Находит лист и проверяет, что книга, которой он принадлежит, доступна пользователю.
 */
final readonly class FindAvailableSheetAction
{
    public function __construct(
        private SheetRepositoryContract $sheets,
        private FindAvailableWorkbookAction $findWorkbook,
    ) {
    }

    /**
     * @throws SheetNotAvailableException
     */
    public function execute(string $sheetIdentifier, string $userIdentifier): SheetModel
    {
        $sheet = $this->sheets->findByIdentifier($sheetIdentifier);

        if ($sheet === null) {
            throw new SheetNotAvailableException();
        }

        try {
            $this->findWorkbook->execute($sheet->workbook_id, $userIdentifier);
        } catch (WorkbookNotAvailableException $exception) {
            throw new SheetNotAvailableException($exception);
        }

        return $sheet;
    }
}
