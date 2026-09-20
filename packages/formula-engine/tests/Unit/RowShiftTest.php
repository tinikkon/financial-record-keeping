<?php

declare(strict_types=1);

use Finance\FormulaEngine\Editing\RowShiftRewriter;
use Finance\FormulaEngine\Lexing\Lexer;

function rewriter(): RowShiftRewriter
{
    return new RowShiftRewriter(new Lexer());
}

test('вставка строки сдвигает ссылки ниже места вставки', function (): void {
    expect(rewriter()->afterInsert('=B3+B10', 5, 1))->toBe('=B3+B11');
});

test('вставка внутрь диапазона растягивает его', function (): void {
    expect(rewriter()->afterInsert('=СУММ(B3:B9)', 5, 1))->toBe('=СУММ(B3:B10)');
});

test('вставка не трогает закрепление, но двигает закреплённую строку', function (): void {
    expect(rewriter()->afterInsert('=D12*$F$2', 2, 1))->toBe('=D13*$F$3');
});

test('вставка оставляет нетронутым всё, кроме ссылок', function (): void {
    expect(rewriter()->afterInsert('=СУММ( B3 : B9 ) + "B3" + 10', 1, 1))
        ->toBe('=СУММ( B4 : B10 ) + "B3" + 10');
});

test('удаление строки подтягивает ссылки снизу', function (): void {
    expect(rewriter()->afterDelete('=B3+B10', 5, 1))->toBe('=B3+B9');
});

test('удаление внутри диапазона сжимает его', function (): void {
    expect(rewriter()->afterDelete('=СУММ(B3:B9)', 5, 1))->toBe('=СУММ(B3:B8)');
});

test('удаление края диапазона придвигает край', function (): void {
    expect(rewriter()->afterDelete('=СУММ(B3:B9)', 9, 1))->toBe('=СУММ(B3:B8)');
});

test('формула на удалённую ячейку неисправима', function (): void {
    expect(rewriter()->afterDelete('=B5*2', 5, 1))->toBeNull();
});

test('диапазон, съеденный целиком, неисправим', function (): void {
    expect(rewriter()->afterDelete('=СУММ(B3:B5)', 3, 3))->toBeNull();
});

test('удаление нескольких строк считается пачкой', function (): void {
    expect(rewriter()->afterDelete('=СУММ(B3:B20)', 5, 4))->toBe('=СУММ(B3:B16)');
});
