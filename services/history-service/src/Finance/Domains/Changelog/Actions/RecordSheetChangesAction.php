<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Actions;

use Carbon\CarbonImmutable;
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
     * @param array<string, mixed> $message
     *
     * @return int сколько записей journal добавлено
     */
    public function execute(array $message): int
    {
        $sheetIdentifier = (string) $message['sheetId'];
        $occurredAt = CarbonImmutable::parse((string) $message['occurredAt']);
        $recorded = 0;

        /** @var array<string, mixed> $cell */
        foreach ((array) $message['cells'] as $cell) {
            $address = (string) $cell['address'];
            $previous = $this->states->find($sheetIdentifier, $address);

            $valueBefore = $previous?->value;
            $valueAfter = $cell['value'] === null ? null : (string) $cell['value'];
            $inputBefore = $previous?->input;
            $inputAfter = $cell['input'] === null ? null : (string) $cell['input'];

            // Изменения, которое ничего не изменило, в журнале быть не должно:
            // пересчёт задевает ячейки, чьё значение осталось прежним.
            if ($valueBefore === $valueAfter && $inputBefore === $inputAfter) {
                continue;
            }

            $this->changes->record([
                'workbook_id' => (string) $message['workbookId'],
                'sheet_id' => $sheetIdentifier,
                'sheet_name' => (string) $message['sheetName'],
                'address' => $address,
                'row' => (int) $cell['row'],
                'column' => (int) $cell['column'],
                'value_before' => $valueBefore,
                'value_after' => $valueAfter,
                'input_before' => $inputBefore,
                'input_after' => $inputAfter,
                'sheet_version' => (int) $message['sheetVersion'],
                'actor_id' => (string) $message['actorId'],
                'occurred_at' => $occurredAt,
            ]);

            $this->states->remember($sheetIdentifier, $address, [
                'workbook_id' => (string) $message['workbookId'],
                'sheet_name' => (string) $message['sheetName'],
                'row' => (int) $cell['row'],
                'column' => (int) $cell['column'],
                'value' => $valueAfter,
                'value_number' => $cell['kind'] === 'text' ? null : $valueAfter,
                'input' => $inputAfter,
            ]);
            $recorded++;
        }

        return $recorded;
    }
}
