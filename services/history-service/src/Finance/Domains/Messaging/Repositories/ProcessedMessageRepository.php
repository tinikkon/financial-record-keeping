<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Repositories;

use Carbon\CarbonImmutable;
use Finance\Domains\Core\Contracts\IndexDefinition;
use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Finance\Domains\Core\Repositories\AbstractMongoRepository;
use Finance\Domains\Messaging\Models\ProcessedMessageModel;
use MongoDB\Driver\Exception\BulkWriteException;

final class ProcessedMessageRepository extends AbstractMongoRepository implements ProvidesIndexes
{
    /**
     * Отмечает сообщение обработанным и сообщает, первое ли это обращение.
     *
     * Проверка «есть ли уже такая запись» с последующей вставкой не годится:
     * между двумя действиями проскакивает второй потребитель. Решает уникальный
     * индекс — отказ базы и есть ответ «уже обработано».
     */
    public function markProcessed(string $messageIdentifier): bool
    {
        try {
            $processed = new ProcessedMessageModel();
            $processed->fill([
                'message_id' => $messageIdentifier,
                'processed_at' => CarbonImmutable::now(),
            ]);
            $processed->save();
        } catch (BulkWriteException) {
            return false;
        }

        return true;
    }

    public function collectionName(): string
    {
        return 'processed_messages';
    }

    public function indexes(): array
    {
        return [
            new IndexDefinition(name: 'processed_messages_unique', keys: ['message_id' => 1], unique: true),
            // Отметки живут неделю: повторная доставка спустя неделю невозможна,
            // а коллекция иначе росла бы без конца.
            new IndexDefinition(
                name: 'processed_messages_expiry',
                keys: ['processed_at' => 1],
                expireAfterSeconds: 604800,
            ),
        ];
    }

    protected function modelClass(): string
    {
        return ProcessedMessageModel::class;
    }
}
