<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Dependencies;

use Finance\FormulaEngine\Values\CellRange;
use Finance\FormulaEngine\Values\CellReference;

/**
 * От чего зависит формула: отдельные ячейки и диапазоны.
 *
 * Диапазоны намеренно не разворачиваются в список ячеек. Формула =СУММ(B3:B90)
 * зависит от 88 ячеек, и хранить их поимённо в базе означало бы 88 записей
 * вместо одной пары границ.
 */
final readonly class CellDependencies
{
    /**
     * @param list<CellReference> $references
     * @param list<CellRange>     $ranges
     */
    public function __construct(
        public array $references,
        public array $ranges,
    ) {
    }

    public static function empty(): self
    {
        return new self([], []);
    }

    /**
     * @return list<string> адреса отдельных ячеек без признаков закрепления
     */
    public function referenceKeys(): array
    {
        return array_values(array_unique(array_map(
            static fn (CellReference $reference): string => $reference->key(),
            $this->references,
        )));
    }

    public function isEmpty(): bool
    {
        return $this->references === [] && $this->ranges === [];
    }
}
