<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Repositories;

use Finance\Domains\Core\Contracts\IndexDefinition;
use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Finance\Domains\Core\Repositories\AbstractMongoRepository;
use Finance\Domains\Messaging\Contracts\OutgoingMessage;
use Finance\Domains\Messaging\Contracts\PendingMessageRepositoryContract;
use Finance\Domains\Messaging\Models\PendingMessageModel;
use Illuminate\Support\Collection;

final class PendingMessageRepository extends AbstractMongoRepository implements PendingMessageRepositoryContract, ProvidesIndexes
{
    public function store(OutgoingMessage $message): PendingMessageModel
    {
        $pending = new PendingMessageModel();
        $pending->fill([
            'routing_key' => $message->routingKey(),
            'message_id' => $message->messageIdentifier(),
            'payload' => $message->body(),
            'attempts' => 0,
        ]);
        $pending->save();

        return $pending;
    }

    public function oldestFirst(int $limit): Collection
    {
        /** @var Collection<int, PendingMessageModel> $messages */
        $messages = $this->query()->orderBy('created_at')->limit($limit)->get();

        return $messages;
    }

    public function forget(string $identifier): void
    {
        $this->query()->where('_id', $identifier)->delete();
    }

    public function countAttempt(string $identifier): void
    {
        $this->query()->where('_id', $identifier)->increment('attempts');
    }

    public function collectionName(): string
    {
        return 'pending_messages';
    }

    public function indexes(): array
    {
        return [
            new IndexDefinition(name: 'pending_messages_order', keys: ['created_at' => 1]),
            new IndexDefinition(name: 'pending_messages_unique', keys: ['message_id' => 1], unique: true),
        ];
    }

    protected function modelClass(): string
    {
        return PendingMessageModel::class;
    }
}
