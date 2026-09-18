<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Console;

use Finance\Domains\Messaging\Contracts\EventPublisherContract;
use Finance\Domains\Messaging\Contracts\PendingMessageRepositoryContract;
use Finance\Domains\Messaging\Services\MessagePublishingFailedException;
use Illuminate\Console\Command;

/**
 * Досылает то, что не ушло, пока очередь была недоступна.
 *
 * Порядок сохраняется: сообщения берутся от самого старого. Первая же неудача
 * прекращает проход — очередь всё ещё лежит, и дальше пытаться бессмысленно.
 */
final class ResendPendingMessagesCommand extends Command
{
    private const int BATCH_SIZE = 100;

    protected $signature = 'finance:resend-pending-messages';

    protected $description = 'Дослать события, не ушедшие в очередь';

    public function handle(
        PendingMessageRepositoryContract $pendingMessages,
        EventPublisherContract $publisher,
    ): int {
        $sent = 0;

        foreach ($pendingMessages->oldestFirst(self::BATCH_SIZE) as $message) {
            try {
                $publisher->publish($message->routing_key, $message->message_id, $message->payload);
            } catch (MessagePublishingFailedException $exception) {
                $pendingMessages->countAttempt((string) $message->_id);
                $this->error("Очередь всё ещё недоступна: {$exception->getMessage()}");

                return self::FAILURE;
            }

            $pendingMessages->forget((string) $message->_id);
            $sent++;
        }

        $this->info("Доставлено сообщений: {$sent}");

        return self::SUCCESS;
    }
}
