<?php

declare(strict_types=1);

test('сводка считает итоги по каждой колонке каждого месяца', function (): void {
    handleEvent(sheetEvent([
        cellPayload('B3', 3, 2, '120'),
        cellPayload('B4', 4, 2, '80'),
        cellPayload('C3', 3, 3, '3500'),
    ]));

    handleEvent(sheetEvent(
        [cellPayload('B3', 3, 2, '200')],
        sheetVersion: 1,
        sheetIdentifier: 'лист-2',
        sheetName: '11.26',
    ));

    $response = $this->withToken(accessTokenFor())
        ->getJson('/api/workbooks/книга-1/summary')
        ->assertOk()
        ->json();

    expect($response['sheets'])->toHaveCount(2);

    $октябрь = collect($response['sheets'])->firstWhere('sheetName', '10.26');
    $колонкаB = collect($октябрь['columns'])->firstWhere('column', 'B');
    $колонкаC = collect($октябрь['columns'])->firstWhere('column', 'C');

    expect($колонкаB['total'])->toBe('200')
        ->and($колонкаB['filledCells'])->toBe(2)
        ->and($колонкаC['total'])->toBe('3500')
        ->and($октябрь['changes'])->toBe(3);
});

test('текст в колонку не суммируется', function (): void {
    handleEvent(sheetEvent([
        ['address' => 'A3', 'row' => 3, 'column' => 1, 'input' => 'Продукты', 'kind' => 'text', 'value' => 'Продукты', 'error' => null, 'format' => []],
        cellPayload('B3', 3, 2, '120'),
    ]));

    $response = $this->withToken(accessTokenFor())->getJson('/api/workbooks/книга-1/summary')->json();
    $колонки = collect($response['sheets'][0]['columns'])->pluck('column')->all();

    expect($колонки)->toBe(['B']);
});

test('итог колонки отражает последнее значение, а не сумму всех правок', function (): void {
    handleEvent(sheetEvent([cellPayload('B3', 3, 2, '120')], sheetVersion: 1));
    handleEvent(sheetEvent([cellPayload('B3', 3, 2, '300')], sheetVersion: 2));

    $response = $this->withToken(accessTokenFor())->getJson('/api/workbooks/книга-1/summary')->json();

    expect($response['sheets'][0]['columns'][0]['total'])->toBe('300');
});

test('состояние листа восстанавливается на прошлую версию', function (): void {
    handleEvent(sheetEvent([cellPayload('B3', 3, 2, '120')], sheetVersion: 1));
    handleEvent(sheetEvent([cellPayload('B4', 4, 2, '80')], sheetVersion: 2));
    handleEvent(sheetEvent([cellPayload('B3', 3, 2, '999')], sheetVersion: 3));

    $наВторойВерсии = $this->withToken(accessTokenFor())
        ->getJson('/api/sheets/лист-1/state?version=2')
        ->assertOk()
        ->json();

    expect($наВторойВерсии['cells']['B3']['value'])->toBe('120')
        ->and($наВторойВерсии['cells']['B4']['value'])->toBe('80');

    $наТретьей = $this->withToken(accessTokenFor())->getJson('/api/sheets/лист-1/state?version=3')->json();

    expect($наТретьей['cells']['B3']['value'])->toBe('999');
});

test('очищенная ячейка не попадает в восстановленное состояние', function (): void {
    handleEvent(sheetEvent([cellPayload('B3', 3, 2, '120')], sheetVersion: 1));
    handleEvent(sheetEvent([cellPayload('B3', 3, 2, null)], sheetVersion: 2));

    $состояние = $this->withToken(accessTokenFor())->getJson('/api/sheets/лист-1/state?version=2')->json();

    expect($состояние['cells'])->toBeEmpty();
});

test('без указания версии состояние не отдаётся', function (): void {
    $this->withToken(accessTokenFor())->getJson('/api/sheets/лист-1/state')->assertStatus(422);
});
