<?php

declare(strict_types=1);

namespace Finance\Domains\Summaries\Data;

/**
 * Содержимое листа на прошлую версию. Пустые в ту версию ячейки не входят.
 */
final readonly class SheetState
{
    /**
     * @param array<string, CellSnapshot> $cellsByAddress
     */
    public function __construct(public array $cellsByAddress)
    {
    }
}
