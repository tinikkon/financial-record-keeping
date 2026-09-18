<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Recalculation;

use Finance\FormulaEngine\Values\CellReference;

/**
 * Строит порядок пересчёта после правки.
 *
 * Сначала обходом в ширину собирается множество задетых ячеек, затем оно
 * упорядочивается по алгоритму Кана. Ячейки, до которых алгоритм не добрался,
 * замкнуты в кольцо ссылок — их значением становится ошибка цикла.
 */
final class RecalculationPlanner
{
    /**
     * @param list<CellReference> $changedReferences
     */
    public function plan(array $changedReferences, DependencyGraph $graph): RecalculationPlan
    {
        /** @var array<string, CellReference> $affected */
        $affected = [];
        /** @var array<string, list<string>> $dependentsByKey */
        $dependentsByKey = [];

        $queue = [];

        foreach ($changedReferences as $reference) {
            $key = $reference->key();

            if (! isset($affected[$key])) {
                $affected[$key] = $reference;
                $queue[] = $reference;
            }
        }

        while ($queue !== []) {
            $current = array_shift($queue);
            $currentKey = $current->key();
            $dependentsByKey[$currentKey] ??= [];

            foreach ($graph->dependentsOf($current) as $dependent) {
                $dependentKey = $dependent->key();
                $dependentsByKey[$currentKey][] = $dependentKey;

                if (isset($affected[$dependentKey])) {
                    continue;
                }

                $affected[$dependentKey] = $dependent;
                $queue[] = $dependent;
            }
        }

        return $this->sortTopologically($affected, $dependentsByKey);
    }

    /**
     * @param array<string, CellReference> $affected
     * @param array<string, list<string>>  $dependentsByKey
     */
    private function sortTopologically(array $affected, array $dependentsByKey): RecalculationPlan
    {
        $incomingCount = array_fill_keys(array_keys($affected), 0);

        foreach ($dependentsByKey as $dependents) {
            foreach ($dependents as $dependentKey) {
                $incomingCount[$dependentKey]++;
            }
        }

        $ready = [];

        foreach ($incomingCount as $key => $count) {
            if ($count === 0) {
                $ready[] = $key;
            }
        }

        /** @var list<CellReference> $order */
        $order = [];

        while ($ready !== []) {
            $key = array_shift($ready);
            $order[] = $affected[$key];

            foreach ($dependentsByKey[$key] ?? [] as $dependentKey) {
                $incomingCount[$dependentKey]--;

                if ($incomingCount[$dependentKey] === 0) {
                    $ready[] = $dependentKey;
                }
            }
        }

        /** @var list<CellReference> $circularReferences */
        $circularReferences = [];

        if (count($order) !== count($affected)) {
            $orderedKeys = array_map(
                static fn (CellReference $reference): string => $reference->key(),
                $order,
            );

            foreach ($affected as $key => $reference) {
                if (! in_array($key, $orderedKeys, true)) {
                    $circularReferences[] = $reference;
                }
            }
        }

        return new RecalculationPlan($order, $circularReferences);
    }
}
