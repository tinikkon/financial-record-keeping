<?php

declare(strict_types=1);

namespace Finance\Domains\Summaries\Actions;

use Finance\Domains\Changelog\Models\CellChangeModel;
use Finance\Domains\Changelog\Repositories\CellChangeRepository;
use Finance\Domains\Summaries\Data\CellSnapshot;
use Finance\Domains\Summaries\Data\SheetState;

/**
 * Восстанавливает содержимое листа на заданную версию.
 *
 * Журнал проигрывается от начала: каждая запись перезаписывает состояние ячейки.
 * Снимков состояния намеренно нет — при двух пользователях журнал короткий, и
 * заводить их сейчас значило бы усложнять код ради оптимизации, которая пока
 * ничего не ускоряет. Место, куда их добавить, здесь одно и очевидное.
 */
final readonly class ReconstructSheetStateAction
{
    public function __construct(private CellChangeRepository $changes)
    {
    }

    public function execute(string $sheetIdentifier, int $version): SheetState
    {
        $cellsByAddress = [];

        foreach ($this->changes->upToVersion($sheetIdentifier, $version) as $change) {
            /** @var CellChangeModel $change */
            $cellsByAddress[$change->address] = new CellSnapshot($change->value_after, $change->input_after);
        }

        return new SheetState(array_filter(
            $cellsByAddress,
            static fn (CellSnapshot $cell): bool => ! $cell->isEmpty(),
        ));
    }
}
