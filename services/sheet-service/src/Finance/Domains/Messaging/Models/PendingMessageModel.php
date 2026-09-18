<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Models;

use Carbon\CarbonImmutable;
use Finance\Domains\Core\Models\MongoModel;

/**
 * Сообщение, которое не удалось опубликовать.
 *
 * Правка таблицы уже применена, и откатывать её из-за недоступной очереди нельзя:
 * очередь нужна только сервису истории, а таблица должна работать всегда.
 * Поэтому неотправленное складывается сюда и досылается командой, когда очередь
 * вернётся.
 *
 * @property string               $_id
 * @property string               $routing_key
 * @property string               $message_id
 * @property array<string, mixed> $payload
 * @property int                  $attempts
 * @property CarbonImmutable      $created_at
 */
final class PendingMessageModel extends MongoModel
{
    protected $table = 'pending_messages';

    protected $fillable = [
        'routing_key',
        'message_id',
        'payload',
        'attempts',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
