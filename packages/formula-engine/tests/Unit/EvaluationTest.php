<?php

declare(strict_types=1);

use Finance\FormulaEngine\FormulaEngine;
use Finance\FormulaEngine\Tests\Support\ArrayCellValueResolver;
use Finance\FormulaEngine\Values\FormulaError;

beforeEach(function (): void {
    $this->engine = FormulaEngine::create();
});

function evaluateFormula(string $formula, array $cells = []): string
{
    return FormulaEngine::create()
        ->evaluate($formula, new ArrayCellValueResolver($cells))
        ->toDisplayString();
}

test('арифметика считается с обычными приоритетами', function (string $formula, string $expected): void {
    expect(evaluateFormula($formula))->toBe($expected);
})->with([
    ['=2+3*4', '14'],
    ['=(2+3)*4', '20'],
    ['=10-4-3', '3'],
    ['=2^3^2', '512'],
    ['=-2^2', '4'],
    ['=-(2^2)', '-4'],
    ['=10/4', '2.5'],
]);

test('деньги складываются точно, без погрешности двоичных дробей', function (): void {
    expect(evaluateFormula('=0.1+0.2'))->toBe('0.3');
});

test('деление на ноль даёт ошибку', function (): void {
    expect(evaluateFormula('=5/0'))->toBe(FormulaError::DivisionByZero->value);
});

test('ссылка берёт значение ячейки', function (): void {
    expect(evaluateFormula('=B3*2', ['B3' => 120]))->toBe('240');
});

test('пустая ячейка в арифметике считается нулём', function (): void {
    expect(evaluateFormula('=B3+5'))->toBe('5');
});

test('текст в арифметике даёт ошибку типа', function (): void {
    $resolver = (new ArrayCellValueResolver())->withText('B3', 'Продукты');

    $value = FormulaEngine::create()->evaluate('=B3+5', $resolver);

    expect($value->toDisplayString())->toBe(FormulaError::WrongValueType->value);
});

test('сумма диапазона пропускает пустые ячейки и подписи', function (): void {
    $resolver = (new ArrayCellValueResolver(['B3' => 120, 'B5' => 80]))
        ->withText('B4', 'Продукты');

    $value = FormulaEngine::create()->evaluate('=СУММ(B3:B6)', $resolver);

    expect($value->toDisplayString())->toBe('200');
});

test('имя функции понимается в любом языке и регистре', function (string $formula): void {
    expect(evaluateFormula($formula, ['B3' => 10, 'B4' => 5]))->toBe('15');
})->with([
    '=СУММ(B3:B4)',
    '=сумм(B3:B4)',
    '=SUM(B3:B4)',
    '=Sum(B3:B4)',
]);

test('сумма принимает вперемешку диапазоны, ссылки и числа', function (): void {
    expect(evaluateFormula('=СУММ(B3:B4;D5;10)', ['B3' => 1, 'B4' => 2, 'D5' => 3]))->toBe('16');
});

test('неизвестная функция даёт ошибку имени', function (): void {
    expect(evaluateFormula('=СРЗНАЧ(B3:B4)'))->toBe(FormulaError::UnknownName->value);
});

test('ошибка распространяется по цепочке ссылок', function (): void {
    $resolver = new ArrayCellValueResolver([
        'B3' => \Finance\FormulaEngine\Values\CellValue::error(FormulaError::DivisionByZero),
    ]);

    $value = FormulaEngine::create()->evaluate('=B3+1', $resolver);

    expect($value->toDisplayString())->toBe(FormulaError::DivisionByZero->value);
});

test('ошибка внутри диапазона портит сумму', function (): void {
    $resolver = new ArrayCellValueResolver([
        'B3' => 10,
        'B4' => \Finance\FormulaEngine\Values\CellValue::error(FormulaError::BrokenReference),
    ]);

    $value = FormulaEngine::create()->evaluate('=СУММ(B3:B4)', $resolver);

    expect($value->toDisplayString())->toBe(FormulaError::BrokenReference->value);
});

test('диапазон вне функции значения не имеет', function (): void {
    expect(evaluateFormula('=B3:B90'))->toBe(FormulaError::WrongValueType->value);
});

test('пересчёт курса из таблицы расходов', function (): void {
    $cells = ['C4' => 3500, 'F2' => '0.03485535'];

    expect(evaluateFormula('=C4*$F$2', $cells))->toBe('121.993725');
});
