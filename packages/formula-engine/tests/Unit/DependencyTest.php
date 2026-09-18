<?php

declare(strict_types=1);

use Finance\FormulaEngine\FormulaEngine;

beforeEach(function (): void {
    $this->engine = FormulaEngine::create();
});

test('из формулы достаются отдельные ссылки', function (): void {
    $dependencies = $this->engine->dependencies('=B3+$F$2*2');

    expect($dependencies->referenceKeys())->toBe(['B3', 'F2'])
        ->and($dependencies->ranges)->toBeEmpty();
});

test('диапазон остаётся диапазоном и не разворачивается в список ячеек', function (): void {
    $dependencies = $this->engine->dependencies('=СУММ(B3:B90)');

    expect($dependencies->references)->toBeEmpty()
        ->and($dependencies->ranges)->toHaveCount(1)
        ->and($dependencies->ranges[0]->toString())->toBe('B3:B90');
});

test('повторная ссылка в списке ключей не дублируется', function (): void {
    expect($this->engine->dependencies('=B3+B3*2')->referenceKeys())->toBe(['B3']);
});

test('формула без ссылок ни от чего не зависит', function (): void {
    expect($this->engine->dependencies('=2+2')->isEmpty())->toBeTrue();
});
