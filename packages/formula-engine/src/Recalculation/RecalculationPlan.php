<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Recalculation;

use Finance\FormulaEngine\Values\CellReference;

/**
 * Что и в каком порядке пересчитывать.
 *
 * В порядок входят и сами изменённые ячейки: если пользователь вписал формулу,
 * её тоже надо вычислить, и сделать это раньше зависящих от неё.
 */
final readonly class RecalculationPlan
{
    /**
     * @param list<CellReference> $order              порядок вычисления
     * @param list<CellReference> $circularReferences ячейки, попавшие в кольцо ссылок
     */
    public function __construct(
        public array $order,
        public array $circularReferences,
    ) {
    }

    public function hasCircularReferences(): bool
    {
        return $this->circularReferences !== [];
    }
}
