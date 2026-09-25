<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Editing;

use Finance\FormulaEngine\Exceptions\InvalidReferenceException;
use Finance\FormulaEngine\Values\CellReference;

/**
 * Направление перестройки листа: вставляются или удаляются строки либо колонки.
 *
 * Правила сдвига для обеих осей одинаковы, разница только в том, какую
 * координату ссылки они трогают. Поэтому ось знает лишь, как эту координату
 * прочитать и подменить, а сами правила живут в одном месте.
 */
enum ShiftAxis: string
{
    case Rows = 'rows';
    case Columns = 'columns';

    public function coordinateOf(CellReference $reference): int
    {
        return match ($this) {
            self::Rows => $reference->row,
            self::Columns => $reference->column,
        };
    }

    /**
     * Та же ссылка с другой координатой по этой оси; закрепление сохраняется.
     *
     * @throws InvalidReferenceException если координата выходит за границы листа
     */
    public function withCoordinate(CellReference $reference, int $coordinate): CellReference
    {
        return new CellReference(
            column: $this === self::Columns ? $coordinate : $reference->column,
            row: $this === self::Rows ? $coordinate : $reference->row,
            columnFixed: $reference->columnFixed,
            rowFixed: $reference->rowFixed,
        );
    }
}
