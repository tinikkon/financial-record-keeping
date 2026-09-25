<?php

declare(strict_types=1);

use Finance\FormulaEngine\Editing\ReferenceShiftRewriter;
use Finance\FormulaEngine\Editing\ShiftAxis;
use Finance\FormulaEngine\Lexing\Lexer;

function rewriter(): ReferenceShiftRewriter
{
    return new ReferenceShiftRewriter(new Lexer());
}

test('вставка строки сдвигает ссылки ниже места вставки', function (): void {
    expect(rewriter()->afterInsert('=B3+B10', ShiftAxis::Rows, 5, 1))->toBe('=B3+B11');
});

test('вставка внутрь диапазона растягивает его', function (): void {
    expect(rewriter()->afterInsert('=СУММ(B3:B9)', ShiftAxis::Rows, 5, 1))->toBe('=СУММ(B3:B10)');
});

test('вставка не трогает закрепление, но двигает закреплённую строку', function (): void {
    expect(rewriter()->afterInsert('=D12*$F$2', ShiftAxis::Rows, 2, 1))->toBe('=D13*$F$3');
});

test('вставка оставляет нетронутым всё, кроме ссылок', function (): void {
    expect(rewriter()->afterInsert('=СУММ( B3 : B9 ) + "B3" + 10', ShiftAxis::Rows, 1, 1))
        ->toBe('=СУММ( B4 : B10 ) + "B3" + 10');
});

test('удаление строки подтягивает ссылки снизу', function (): void {
    expect(rewriter()->afterDelete('=B3+B10', ShiftAxis::Rows, 5, 1))->toBe('=B3+B9');
});

test('удаление внутри диапазона сжимает его', function (): void {
    expect(rewriter()->afterDelete('=СУММ(B3:B9)', ShiftAxis::Rows, 5, 1))->toBe('=СУММ(B3:B8)');
});

test('удаление края диапазона придвигает край', function (): void {
    expect(rewriter()->afterDelete('=СУММ(B3:B9)', ShiftAxis::Rows, 9, 1))->toBe('=СУММ(B3:B8)');
});

test('формула на удалённую ячейку неисправима', function (): void {
    expect(rewriter()->afterDelete('=B5*2', ShiftAxis::Rows, 5, 1))->toBeNull();
});

test('диапазон, съеденный целиком, неисправим', function (): void {
    expect(rewriter()->afterDelete('=СУММ(B3:B5)', ShiftAxis::Rows, 3, 3))->toBeNull();
});

test('удаление нескольких строк считается пачкой', function (): void {
    expect(rewriter()->afterDelete('=СУММ(B3:B20)', ShiftAxis::Rows, 5, 4))->toBe('=СУММ(B3:B16)');
});

test('вставка колонки сдвигает ссылки правее места вставки', function (): void {
    expect(rewriter()->afterInsert('=A3+C3', ShiftAxis::Columns, 2, 1))->toBe('=A3+D3');
});

test('вставка колонки растягивает диапазон и двигает закреплённую колонку', function (): void {
    expect(rewriter()->afterInsert('=СУММ(A1:D1)*$C$2', ShiftAxis::Columns, 3, 2))->toBe('=СУММ(A1:F1)*$E$2');
});

test('сдвиг колонки переходит через Z', function (): void {
    expect(rewriter()->afterInsert('=Z1+AA1', ShiftAxis::Columns, 1, 1))->toBe('=AA1+AB1')
        ->and(rewriter()->afterDelete('=AA1', ShiftAxis::Columns, 1, 1))->toBe('=Z1');
});

test('удаление колонки не трогает строки', function (): void {
    expect(rewriter()->afterDelete('=C5+C10', ShiftAxis::Columns, 2, 1))->toBe('=B5+B10');
});

test('удаление колонки внутри диапазона сжимает его', function (): void {
    expect(rewriter()->afterDelete('=СУММ(B2:E2)', ShiftAxis::Columns, 3, 1))->toBe('=СУММ(B2:D2)');
});

test('формула на удалённую колонку неисправима', function (): void {
    expect(rewriter()->afterDelete('=B5*2', ShiftAxis::Columns, 2, 1))->toBeNull()
        ->and(rewriter()->afterDelete('=СУММ(B1:C1)', ShiftAxis::Columns, 2, 2))->toBeNull();
});
