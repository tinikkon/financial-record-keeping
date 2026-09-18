<?php

declare(strict_types=1);

namespace Finance\Domains\Workbooks\Models;

use Carbon\CarbonImmutable;
use Finance\Domains\Core\Models\MongoModel;

/**
 * Книга — набор листов-месяцев. Пользователей двое, книга одна, но модель
 * рассчитана на несколько: это ничего не усложняет и снимает потолок на будущее.
 *
 * @property string          $_id
 * @property string          $name
 * @property string          $owner_id
 * @property list<string>    $member_ids
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
final class WorkbookModel extends MongoModel
{
    protected $table = 'workbooks';

    protected $fillable = [
        'name',
        'owner_id',
        'member_ids',
    ];

    protected $casts = [
        'member_ids' => 'array',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function identifier(): string
    {
        return (string) $this->_id;
    }

    public function isAvailableTo(string $userIdentifier): bool
    {
        return $this->owner_id === $userIdentifier
            || in_array($userIdentifier, $this->member_ids ?? [], true);
    }
}
