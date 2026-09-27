<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Actions;

use Finance\Domains\Changelog\Actions\RecordSheetChangesAction;
use Finance\Domains\Changelog\Data\SheetChangedMessage;
use Finance\Domains\Messaging\Repositories\ProcessedMessageRepository;

/**
 * Обрабатывает одно сообщение из очереди.
 *
 * Вынесено из команды-потребителя, чтобы поведение можно было проверить тестом
 * без запущенного брокера.
 */
final readonly class HandleSheetEventAction
{
    public function __construct(
        private ProcessedMessageRepository $processedMessages,
        private RecordSheetChangesAction $recordChanges,
    ) {
    }

    /**
     * @return int сколько записей добавлено в журнал; ноль при повторной доставке
     */
    public function execute(SheetChangedMessage $message): int
    {
        if (! $this->processedMessages->markProcessed($message->messageIdentifier)) {
            return 0;
        }

        return $this->recordChanges->execute($message);
    }
}
