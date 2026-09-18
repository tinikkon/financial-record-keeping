<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Models;

use Carbon\CarbonImmutable;
use Finance\Domains\Core\Models\MongoModel;

/**
 * Лист книги — один месяц.
 *
 * Поле version растёт с каждой применённой правкой и служит опорой синхронизации:
 * клиент знает, какую версию он показывает, и по разрыву в номерах понимает,
 * что до него что-то не доехало.
 *
 * @property string              $_id
 * @property string              $workbook_id
 * @property string              $name
 * @property int                 $position
 * @property int                 $version
 * @property int                 $row_count
 * @property int                 $column_count
 * @property array<string, int>  $column_widths
 * @property CarbonImmutable     $created_at
 * @property CarbonImmutable     $updated_at
 */
final class SheetModel extends MongoModel
{
    public const int DEFAULT_ROW_COUNT = 200;

    public const int DEFAULT_COLUMN_COUNT = 26;

    protected $table = 'sheets';

    protected $fillable = [
        'workbook_id',
        'name',
        'position',
        'version',
        'row_count',
        'column_count',
        'column_widths',
    ];

    protected $casts = [
        'position' => 'integer',
        'version' => 'integer',
        'row_count' => 'integer',
        'column_count' => 'integer',
        'column_widths' => 'array',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function identifier(): string
    {
        return (string) $this->_id;
    }
}
