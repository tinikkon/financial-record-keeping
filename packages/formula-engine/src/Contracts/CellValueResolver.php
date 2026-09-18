<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Contracts;

use Finance\FormulaEngine\Values\CellRange;
use Finance\FormulaEngine\Values\CellReference;
use Finance\FormulaEngine\Values\CellValue;

/**
 * Источник значений ячеек для вычислителя.
 *
 * Благодаря этому интерфейсу движок не знает ни про базу данных, ни про Laravel:
 * в приложении его реализует запрос к MongoDB, в тестах — массив в памяти.
 */
interface CellValueResolver
{
    public function valueAt(CellReference $reference): CellValue;

    /**
     * @return list<CellValue> значения всех ячеек диапазона, включая пустые
     */
    public function valuesIn(CellRange $range): array;
}
