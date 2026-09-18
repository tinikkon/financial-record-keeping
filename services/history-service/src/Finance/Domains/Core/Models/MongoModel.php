<?php

declare(strict_types=1);

namespace Finance\Domains\Core\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Общий предок моделей. Фиксирует подключение, чтобы ни одна модель случайно
 * не ушла в другое хранилище.
 */
abstract class MongoModel extends Model
{
    protected $connection = 'mongodb';
}
