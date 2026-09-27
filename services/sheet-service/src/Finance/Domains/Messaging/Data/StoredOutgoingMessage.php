<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Data;

use Finance\Domains\Messaging\Contracts\OutgoingMessage;
use Finance\Domains\Messaging\Models\PendingMessageModel;

/**
 * Неотправленное сообщение, поднятое из хранилища для повторной отправки.
 *
 * Тело берётся ровно тем, каким его сохранили: пересобирать событие заново
 * нельзя — ячейки с тех пор могли измениться, а журнал должен получить то,
 * что было на момент правки.
 */
final readonly class StoredOutgoingMessage implements OutgoingMessage
{
    /**
     * @param array<string, mixed> $body
     */
    public function __construct(
        private string $routingKey,
        private string $messageIdentifier,
        private array $body,
    ) {
    }

    public static function fromModel(PendingMessageModel $message): self
    {
        return new self($message->routing_key, $message->message_id, $message->payload);
    }

    public function routingKey(): string
    {
        return $this->routingKey;
    }

    public function messageIdentifier(): string
    {
        return $this->messageIdentifier;
    }

    public function body(): array
    {
        return $this->body;
    }
}
