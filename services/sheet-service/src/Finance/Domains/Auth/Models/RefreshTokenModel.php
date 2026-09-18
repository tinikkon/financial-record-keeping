<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Models;

use Carbon\CarbonImmutable;
use Finance\Domains\Core\Models\MongoModel;

/**
 * Токен обновления. Хранится только его отпечаток: утечка копии базы
 * не даёт возможности войти.
 *
 * @property string          $_id
 * @property string          $user_id
 * @property string          $token_hash
 * @property CarbonImmutable $expires_at
 * @property CarbonImmutable $created_at
 */
final class RefreshTokenModel extends MongoModel
{
    protected $table = 'refresh_tokens';

    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];
}
