<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Resources;

use Finance\Domains\Cells\Data\AppliedCellEdits;

/**
 * Ответ на любую правку листа — ввод, оформление, перестройку, восстановление.
 * Форма одна, и клиент применяет все эти ответы одним кодом.
 */
final readonly class AppliedCellEditsResource
{
    /**
     * @return array{sheetVersion: int, cells: list<array<string, mixed>>}
     */
    public static function toArray(AppliedCellEdits $applied): array
    {
        return [
            'sheetVersion' => $applied->sheetVersion,
            'cells' => array_map(CellResource::toArray(...), $applied->cells),
        ];
    }
}
