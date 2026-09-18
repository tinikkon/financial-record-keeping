<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Models;

use Brick\Math\BigDecimal;
use Finance\Domains\Core\Casts\Decimal128Cast;
use Finance\Domains\Core\Models\MongoModel;

/**
 * Последнее известное содержимое ячейки.
 *
 * Сервис таблиц присылает только новое значение — прежнее он уже затёр. Чтобы
 * журнал показывал «было двести, стало триста», прошлое состояние хранится здесь
 * и обновляется при каждом изменении.
 *
 * Число хранится ещё и отдельным полем типа Decimal128: по строке нельзя ни
 * просуммировать, ни отсортировать, а сводки строятся именно суммированием.
 *
 * @property string          $_id
 * @property string          $workbook_id
 * @property string          $sheet_id
 * @property string          $sheet_name
 * @property string          $address
 * @property int             $row
 * @property int             $column
 * @property string|null     $value
 * @property BigDecimal|null $value_number
 * @property string|null     $input
 */
final class CellStateModel extends MongoModel
{
    protected $table = 'cell_states';

    protected $fillable = [
        'workbook_id',
        'sheet_id',
        'sheet_name',
        'address',
        'row',
        'column',
        'value',
        'value_number',
        'input',
    ];

    protected $casts = [
        'row' => 'integer',
        'column' => 'integer',
        'value_number' => Decimal128Cast::class,
    ];
}
