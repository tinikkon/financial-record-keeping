<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Models;

use Carbon\CarbonImmutable;
use Finance\Domains\Core\Models\MongoModel;

/**
 * Запись журнала: что было в ячейке и что стало.
 *
 * Коллекция только пополняется. Записи не правятся и не удаляются — журнал,
 * который можно переписать, не журнал.
 *
 * @property string          $_id
 * @property string          $workbook_id
 * @property string          $sheet_id
 * @property string          $sheet_name
 * @property string          $address
 * @property int             $row
 * @property int             $column
 * @property string|null     $value_before
 * @property string|null     $value_after
 * @property string|null     $input_before
 * @property string|null     $input_after
 * @property int             $sheet_version
 * @property string          $actor_id
 * @property CarbonImmutable $occurred_at
 */
final class CellChangeModel extends MongoModel
{
    protected $table = 'cell_changes';

    protected $fillable = [
        'workbook_id',
        'sheet_id',
        'sheet_name',
        'address',
        'row',
        'column',
        'value_before',
        'value_after',
        'input_before',
        'input_after',
        'sheet_version',
        'actor_id',
        'occurred_at',
    ];

    protected $casts = [
        'row' => 'integer',
        'column' => 'integer',
        'sheet_version' => 'integer',
        'occurred_at' => 'immutable_datetime',
    ];
}
