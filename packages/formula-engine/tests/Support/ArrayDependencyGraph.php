<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Tests\Support;

use Finance\FormulaEngine\Recalculation\DependencyGraph;
use Finance\FormulaEngine\Values\CellReference;

/**
 * Обратные связи листа, заданные вручную: адрес ячейки к адресам тех,
 * кто на неё ссылается.
 */
final class ArrayDependencyGraph implements DependencyGraph
{
    /**
     * @param array<string, list<string>> $dependentsByAddress
     */
    public function __construct(private readonly array $dependentsByAddress)
    {
    }

    public function dependentsOf(CellReference $reference): array
    {
        return array_map(
            static fn (string $address): CellReference => CellReference::fromString($address),
            $this->dependentsByAddress[$reference->key()] ?? [],
        );
    }
}
