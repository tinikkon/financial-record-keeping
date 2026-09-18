<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Recalculation;

use Finance\FormulaEngine\Values\CellReference;

/**
 * Обратные связи листа: кто ссылается на данную ячейку.
 *
 * В приложении это запрос к MongoDB по индексу зависимостей, в тестах — массив.
 */
interface DependencyGraph
{
    /**
     * @return list<CellReference> ячейки, чьи формулы прямо ссылаются на указанную
     */
    public function dependentsOf(CellReference $reference): array;
}
