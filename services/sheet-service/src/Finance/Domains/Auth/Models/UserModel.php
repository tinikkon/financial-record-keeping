<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Models;

use Carbon\CarbonImmutable;
use Finance\Domains\Core\Models\MongoModel;

/**
 * Пользователь приложения.
 *
 * @property string         $_id
 * @property string         $email
 * @property string         $name
 * @property string         $password_hash
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
final class UserModel extends MongoModel
{
    protected $table = 'users';

    protected $fillable = [
        'email',
        'name',
        'password_hash',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function identifier(): string
    {
        return (string) $this->_id;
    }
}
