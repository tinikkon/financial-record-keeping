<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Repositories;

use Finance\Domains\Core\Contracts\IndexDefinition;
use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Finance\Domains\Core\Repositories\AbstractMongoRepository;
use Finance\Domains\Messaging\Contracts\PendingMessageRepositoryContract;
use Finance\Domains\Messaging\Models\PendingMessageModel;
use Illuminate\Support\Collection;

final class PendingMessageRepository extends AbstractMongoRepository implements PendingMessageRepositoryContract, ProvidesIndexes
{
    public function store(string $routingKey, string $messageIdentifier, array $payload): PendingMessageModel
    {
        $message = new PendingMessageModel();
        $message->fill([
            'routing_key' => $routingKey,
            'message_id' => $messageIdentifier,
            'payload' => $payload,
            'attempts' => 0,
        ]);
        $message->save();

        return $message;
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
