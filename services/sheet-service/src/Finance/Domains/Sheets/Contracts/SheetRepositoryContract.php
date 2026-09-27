<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Contracts;

use Finance\Domains\Sheets\Data\ColumnWidths;
use Finance\Domains\Sheets\Models\SheetModel;
use Illuminate\Support\Collection;

interface SheetRepositoryContract
{
    /**
     * @return Collection<int, SheetModel>
     */
    public function forWorkbook(string $workbookIdentifier): Collection;

    public function findByIdentifier(string $identifier): ?SheetModel;

    public function findByName(string $workbookIdentifier, string $name): ?SheetModel;

    public function create(
        string $workbookIdentifier,
        string $name,
        int $position,
        int $rowCount,
        int $columnCount,
        ColumnWidths $columnWidths = new ColumnWidths(),
    ): SheetModel;

    public function rename(string $identifier, string $name): void;

    public function delete(string $identifier): void;

    /**
     * @param array<string, int> $positionsByIdentifier
     */
    public function updatePositions(array $positionsByIdentifier): void;

    public function updateColumnWidths(string $identifier, ColumnWidths $columnWidths): void;

    public function nextPosition(string $workbookIdentifier): int;

    /**
     * Увеличивает версию листа одной атомарной операцией базы и возвращает новое
     * значение. Читать, прибавлять и записывать нельзя: два одновременных запроса
     * получили бы одинаковую версию.
     */
    public function incrementVersion(string $identifier): int;
}
