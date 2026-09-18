<?php

declare(strict_types=1);

use Finance\FormulaEngine\Recalculation\RecalculationPlanner;
use Finance\FormulaEngine\Tests\Support\ArrayDependencyGraph;
use Finance\FormulaEngine\Values\CellReference;

function planFor(array $changed, array $dependents): array
{
    $plan = (new RecalculationPlanner())->plan(
        array_map(static fn (string $address): CellReference => CellReference::fromString($address), $changed),
        new ArrayDependencyGraph($dependents),
    );

    return [
        'order' => array_map(static fn (CellReference $reference): string => $reference->key(), $plan->order),
        'circular' => array_map(static fn (CellReference $reference): string => $reference->key(), $plan->circularReferences),
    ];
}

test('изменённая ячейка идёт раньше зависящих от неё', function (): void {
    $plan = planFor(['B3'], ['B3' => ['B91'], 'B91' => ['E94']]);

    expect($plan['order'])->toBe(['B3', 'B91', 'E94'])
        ->and($plan['circular'])->toBeEmpty();
});

test('ячейка с двумя источниками пересчитывается после обоих', function (): void {
    $plan = planFor(['B3', 'C3'], ['B3' => ['D3'], 'C3' => ['D3']]);

    expect(array_slice($plan['order'], -1))->toBe(['D3'])
        ->and($plan['order'])->toHaveCount(3);
});

test('ячейки без зависимых дают план из самих себя', function (): void {
    expect(planFor(['B3'], [])['order'])->toBe(['B3']);
});

test('кольцо ссылок обнаруживается и не попадает в порядок пересчёта', function (): void {
    $plan = planFor(['A1'], ['A1' => ['A2'], 'A2' => ['A3'], 'A3' => ['A1']]);

    expect($plan['circular'])->toEqualCanonicalizing(['A1', 'A2', 'A3'])
        ->and($plan['order'])->toBeEmpty();
});

test('ячейка, ссылающаяся сама на себя, попадает в кольцо', function (): void {
    expect(planFor(['A1'], ['A1' => ['A1']])['circular'])->toBe(['A1']);
});

test('здоровая часть листа пересчитывается, даже если в другом месте кольцо', function (): void {
    $plan = planFor(['B3'], ['B3' => ['B4'], 'B4' => [], 'A1' => ['A2'], 'A2' => ['A1']]);

    expect($plan['order'])->toBe(['B3', 'B4'])
        ->and($plan['circular'])->toBeEmpty();
});
