<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Actions;

use Carbon\CarbonImmutable;
use Finance\Domains\Cells\Data\AppliedCellEdits;
use Finance\Domains\Messaging\Contracts\EventPublisherContract;
use Finance\Domains\Messaging\Contracts\PendingMessageRepositoryContract;
use Finance\Domains\Messaging\Data\CellsChangedMessage;
use Finance\Domains\Messaging\Services\MessagePublishingFailedException;
use Finance\Domains\Sheets\Models\SheetModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Отправляет сервису истории сведения о том, что изменилось.
 *
 * Вызывается после завершённой транзакции, а не внутри неё: сообщение об изменении,
 * которое потом откатилось, породило бы в журнале запись о несуществовавшей правке.
 */
final readonly class PublishCellsChangedAction
{
    public function __construct(
        private EventPublisherContract $publisher,
        private PendingMessageRepositoryContract $pendingMessages,
    ) {
    }

    public function execute(SheetModel $sheet, AppliedCellEdits $changes, string $actorIdentifier): void
    {
        $message = new CellsChangedMessage(
            routingKey: (string) config('messaging.routing_keys.cells_changed'),
            messageIdentifier: (string) Str::uuid(),
            occurredAt: CarbonImmutable::now(),
            sheet: $sheet,
            changes: $changes,
            actorIdentifier: $actorIdentifier,
        );

        try {
            $this->publisher->publish($message);
        } catch (MessagePublishingFailedException $exception) {
            Log::warning('Событие не ушло в очередь, отложено до восстановления', [
                'routingKey' => $message->routingKey(),
                'messageId' => $message->messageIdentifier(),
                'exception' => $exception->getMessage(),
            ]);

            $this->pendingMessages->store($message);
        }
    }
}
