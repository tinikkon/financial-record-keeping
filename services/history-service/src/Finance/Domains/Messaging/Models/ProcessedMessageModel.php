<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Models;

use Carbon\CarbonImmutable;
use Finance\Domains\Core\Models\MongoModel;

/**
 * Отметка о том, что сообщение уже обработано.
 *
 * RabbitMQ обещает доставку «не менее одного раза»: при обрыве связи до
 * подтверждения сообщение придёт повторно. Без этой отметки одна правка
 * попала бы в журнал дважды.
 *
 * @property string          $_id
 * @property string          $message_id
 * @property CarbonImmutable $processed_at
 */
final class ProcessedMessageModel extends MongoModel
{
    protected $table = 'processed_messages';

    protected $fillable = [
        'message_id',
        'processed_at',
    ];

    protected $casts = [
        'processed_at' => 'immutable_datetime',
    ];
}
