<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Models;

use Finance\Domains\Core\Models\MongoModel;

/**
 * Последнее известное содержимое ячейки.
 *
 * Сервис таблиц присылает только новое значение — прежнее он уже затёр. Чтобы
 * журнал показывал «было двести, стало триста», прошлое состояние хранится здесь
 * и обновляется при каждом изменении.
 *
 * @property string      $_id
 * @property string      $sheet_id
 * @property string      $address
 * @property string|null $value
 * @property string|null $input
 */
final class CellStateModel extends MongoModel
{
    protected $table = 'cell_states';

    protected $fillable = [
        'sheet_id',
        'address',
        'value',
        'input',
    ];
}
