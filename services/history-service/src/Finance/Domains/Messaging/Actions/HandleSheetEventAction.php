<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Actions;

use Finance\Domains\Changelog\Actions\RecordSheetChangesAction;
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
     * @param array<string, mixed> $message
     *
     * @return int сколько записей добавлено в журнал; ноль при повторной доставке
     */
    public function execute(array $message): int
    {
        if (! $this->processedMessages->markProcessed((string) $message['messageId'])) {
            return 0;
        }

        return $this->recordChanges->execute($message);
    }
}
