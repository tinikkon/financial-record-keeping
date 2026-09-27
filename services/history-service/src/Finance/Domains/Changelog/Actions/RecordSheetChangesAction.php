<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Actions;

use Finance\Domains\Changelog\Data\CellStateUpdate;
use Finance\Domains\Changelog\Data\NewCellChange;
use Finance\Domains\Changelog\Data\SheetChangedMessage;
use Finance\Domains\Changelog\Repositories\CellChangeRepository;
use Finance\Domains\Changelog\Repositories\CellStateRepository;

/**
 * Превращает сообщение об изменении в записи журнала.
 *
 * Прежнее содержимое берётся из собственного хранилища состояний: сервис таблиц
 * присылает только новое, старое он у себя уже затёр.
 */
final readonly class RecordSheetChangesAction
{
    public function __construct(
        private CellChangeRepository $changes,
        private CellStateRepository $states,
    ) {
    }

    /**
     * @return int сколько записей добавлено в журнал
     */
    public function execute(SheetChangedMessage $message): int
    {
        $recorded = 0;

        foreach ($message->cells as $cell) {
            $previous = $this->states->find($message->sheetIdentifier, $cell->address);
            $valueBefore = $previous?->value;
            $inputBefore = $previous?->input;

            // Изменения, которое ничего не изменило, в журнале быть не должно:
            // пересчёт задевает ячейки, чьё значение осталось прежним.
            if ($valueBefore === $cell->value && $inputBefore === $cell->input) {
                continue;
            }

            $this->changes->record(new NewCellChange(
                workbookIdentifier: $message->workbookIdentifier,
                sheetIdentifier: $message->sheetIdentifier,
                sheetName: $message->sheetName,
                address: $cell->address,
                row: $cell->row,
                column: $cell->column,
                valueBefore: $valueBefore,
                valueAfter: $cell->value,
                inputBefore: $inputBefore,
                inputAfter: $cell->input,
                sheetVersion: $message->sheetVersion,
                actorIdentifier: $message->actorIdentifier,
                occurredAt: $message->occurredAt,
            ));

            $this->states->remember(new CellStateUpdate(
                workbookIdentifier: $message->workbookIdentifier,
                sheetIdentifier: $message->sheetIdentifier,
                sheetName: $message->sheetName,
                address: $cell->address,
                row: $cell->row,
                column: $cell->column,
                value: $cell->value,
                numberValue: $cell->numberValue(),
                input: $cell->input,
            ));

            $recorded++;
        }

        return $recorded;
    }
}
