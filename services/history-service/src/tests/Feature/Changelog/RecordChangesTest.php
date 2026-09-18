<?php

declare(strict_types=1);

use Finance\Domains\Changelog\Models\CellChangeModel;

test('первое изменение ячейки записывается с пустым прежним значением', function (): void {
    $recorded = handleEvent(sheetEvent([cellPayload('B3', 3, 2, '120')]));

    $change = CellChangeModel::query()->first();

    expect($recorded)->toBe(1)
        ->and($change->address)->toBe('B3')
        ->and($change->value_before)->toBeNull()
        ->and($change->value_after)->toBe('120')
        ->and($change->sheet_name)->toBe('10.26');
});

test('журнал показывает, что было и что стало', function (): void {
    handleEvent(sheetEvent([cellPayload('B3', 3, 2, '120')], sheetVersion: 1));
    handleEvent(sheetEvent([cellPayload('B3', 3, 2, '300')], sheetVersion: 2));

    $latest = CellChangeModel::query()->orderBy('sheet_version', 'desc')->first();

    expect(CellChangeModel::query()->count())->toBe(2)
        ->and($latest->value_before)->toBe('120')
        ->and($latest->value_after)->toBe('300');
});

test('ячейка, значение которой не изменилось, в журнал не попадает', function (): void {
    handleEvent(sheetEvent([cellPayload('B3', 3, 2, '120')], sheetVersion: 1));

    $recorded = handleEvent(sheetEvent([cellPayload('B3', 3, 2, '120')], sheetVersion: 2));

    expect($recorded)->toBe(0)
        ->and(CellChangeModel::query()->count())->toBe(1);
});

test('повторная доставка того же сообщения журнал не дублирует', function (): void {
    $message = sheetEvent([cellPayload('B3', 3, 2, '120')]);

    $first = handleEvent($message);
    $second = handleEvent($message);

    expect($first)->toBe(1)
        ->and($second)->toBe(0)
        ->and(CellChangeModel::query()->count())->toBe(1);
});

test('пачка изменений записывается целиком', function (): void {
    $recorded = handleEvent(sheetEvent([
        cellPayload('B3', 3, 2, '120'),
        cellPayload('B4', 4, 2, '80'),
        cellPayload('B91', 91, 2, '200', '=СУММ(B3:B90)'),
    ]));

    expect($recorded)->toBe(3)
        ->and(CellChangeModel::query()->where('address', 'B91')->first()->input_after)->toBe('=СУММ(B3:B90)');
});

test('очистка ячейки тоже попадает в журнал', function (): void {
    handleEvent(sheetEvent([cellPayload('B3', 3, 2, '120')], sheetVersion: 1));
    handleEvent(sheetEvent([cellPayload('B3', 3, 2, null)], sheetVersion: 2));

    $latest = CellChangeModel::query()->orderBy('sheet_version', 'desc')->first();

    expect($latest->value_before)->toBe('120')
        ->and($latest->value_after)->toBeNull();
});
