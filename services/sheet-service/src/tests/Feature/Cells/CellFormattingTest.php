<?php

declare(strict_types=1);

test('заливка накладывается на диапазон', function (): void {
    $context = sheetContext();

    $response = test()->withToken($context['token'])
        ->patchJson("/api/sheets/{$context['sheet']}/cells/format", [
            'range' => ['startRow' => 94, 'startColumn' => 4, 'endRow' => 94, 'endColumn' => 5],
            'format' => ['background' => '#FFFF00', 'bold' => true],
        ])
        ->assertOk()
        ->json();

    expect($response['cells'])->toHaveCount(2)
        ->and($response['cells'][0]['format']['background'])->toBe('#FFFF00')
        ->and($response['cells'][0]['format']['bold'])->toBeTrue();
});

test('оформление ячейки переживает правку её содержимого', function (): void {
    $context = sheetContext();

    test()->withToken($context['token'])->patchJson("/api/sheets/{$context['sheet']}/cells/format", [
        'range' => ['startRow' => 3, 'startColumn' => 2, 'endRow' => 3, 'endColumn' => 2],
        'format' => ['background' => '#FFFF00'],
    ]);

    $response = writeCells($context, ['B3' => '120']);

    expect(valueAt($response, 'B3'))->toBe('120')
        ->and($response['cells'][0]['format']['background'])->toBe('#FFFF00');
});

test('снятие свойства не сбрасывает остальные', function (): void {
    $context = sheetContext();
    $range = ['startRow' => 3, 'startColumn' => 2, 'endRow' => 3, 'endColumn' => 2];

    test()->withToken($context['token'])->patchJson("/api/sheets/{$context['sheet']}/cells/format", [
        'range' => $range,
        'format' => ['background' => '#FFFF00', 'bold' => true],
    ]);

    $response = test()->withToken($context['token'])
        ->patchJson("/api/sheets/{$context['sheet']}/cells/format", [
            'range' => $range,
            'format' => ['background' => null],
        ])
        ->json();

    expect($response['cells'][0]['format'])->not->toHaveKey('background')
        ->and($response['cells'][0]['format']['bold'])->toBeTrue();
});

test('неверный цвет отвергается', function (): void {
    $context = sheetContext();

    test()->withToken($context['token'])
        ->patchJson("/api/sheets/{$context['sheet']}/cells/format", [
            'range' => ['startRow' => 1, 'startColumn' => 1, 'endRow' => 1, 'endColumn' => 1],
            'format' => ['background' => 'жёлтый'],
        ])
        ->assertStatus(422);
});
