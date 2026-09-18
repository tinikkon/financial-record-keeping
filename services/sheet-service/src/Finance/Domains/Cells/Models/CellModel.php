<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Models;

use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Finance\Domains\Cells\Enums\CellKind;
use Finance\Domains\Core\Casts\Decimal128Cast;
use Finance\Domains\Core\Models\MongoModel;
use Finance\FormulaEngine\Values\CellReference;

/**
 * Ячейка листа.
 *
 * Ячейки лежат отдельной коллекцией, а не массивом внутри документа листа.
 * При вложенном массиве правка одной ячейки означала бы перезапись всего листа:
 * двое правящих разные ячейки затирали бы друг друга. Плюс документ ограничен
 * шестнадцатью мегабайтами, а поиск зависимых ячеек стал бы перебором в памяти
 * вместо запроса по индексу.
 *
 * @property string                      $_id
 * @property string                      $sheet_id
 * @property int                         $row
 * @property int                         $column
 * @property string|null                 $input
 * @property string                      $kind
 * @property BigDecimal|null             $value_number
 * @property string|null                 $value_text
 * @property string|null                 $error
 * @property array<string, mixed>        $format
 * @property list<string>                $depends_on_cells
 * @property list<array<string, int>>    $depends_on_ranges
 * @property string|null                 $updated_by
 * @property CarbonImmutable             $updated_at
 */
final class CellModel extends MongoModel
{
    protected $table = 'cells';

    protected $fillable = [
        'sheet_id',
        'row',
        'column',
        'input',
        'kind',
        'value_number',
        'value_text',
        'error',
        'format',
        'depends_on_cells',
        'depends_on_ranges',
        'updated_by',
    ];

    protected $casts = [
        'row' => 'integer',
        'column' => 'integer',
        'value_number' => Decimal128Cast::class,
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    public function cellKind(): CellKind
    {
        return CellKind::from($this->kind);
    }

    public function reference(): CellReference
    {
        return new CellReference($this->column, $this->row);
    }

    public function address(): string
    {
        return CellReference::columnToLetters($this->column) . $this->row;
    }

    public function isEmpty(): bool
    {
        return $this->kind === CellKind::Empty->value;
    }
}
