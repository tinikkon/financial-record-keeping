<?php

declare(strict_types=1);

use Finance\Domains\Sheets\Models\SheetModel;
use Finance\FormulaEngine\Values\CellReference;
use Finance\FormulaEngine\Values\FormulaError;

test('вставка строки переселяет содержимое вниз', function (): void {
    $context = sheetContext();

    writeCells($context, ['A3' => 'Продукты', 'A4' => 'Бензин']);

    test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/rows/insert", ['row' => 3])
        ->assertOk();

    $content = test()->withToken($context['token'])
        ->getJson("/api/sheets/{$context['sheet']}/cells")
        ->json();

    expect(valueAt($content, 'A3'))->toBeNull()
        ->and(valueAt($content, 'A4'))->toBe('Продукты')
        ->and(valueAt($content, 'A5'))->toBe('Бензин');
});

test('вставка сдвигает ссылки формул и итог не меняется', function (): void {
    $context = sheetContext();

    writeCells($context, ['B3' => '120', 'B4' => '80', 'B10' => '=СУММ(B3:B9)']);

    test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/rows/insert", ['row' => 4])
        ->assertOk();

    $content = test()->withToken($context['token'])
        ->getJson("/api/sheets/{$context['sheet']}/cells")
        ->json();

    expect(inputAt($content, 'B11'))->toBe('=СУММ(B3:B10)')
        ->and(valueAt($content, 'B11'))->toBe('200')
        ->and(valueAt($content, 'B5'))->toBe('80');
});

test('удаление строки поднимает нижние и сжимает диапазон', function (): void {
    $context = sheetContext();

    writeCells($context, ['B3' => '120', 'B4' => '80', 'B5' => '50', 'B10' => '=СУММ(B3:B9)']);

    test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/rows/delete", ['row' => 4])
        ->assertOk();

    $content = test()->withToken($context['token'])
        ->getJson("/api/sheets/{$context['sheet']}/cells")
        ->json();

    expect(valueAt($content, 'B4'))->toBe('50')
        ->and(inputAt($content, 'B9'))->toBe('=СУММ(B3:B8)')
        ->and(valueAt($content, 'B9'))->toBe('170');
});

test('формула, потерявшая опору, становится ошибкой ссылки', function (): void {
    $context = sheetContext();

    writeCells($context, ['B5' => '120', 'C1' => '=B5*2']);

    test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/rows/delete", ['row' => 5])
        ->assertOk();

    $content = test()->withToken($context['token'])
        ->getJson("/api/sheets/{$context['sheet']}/cells")
        ->json();

    expect(valueAt($content, 'C1'))->toBe(FormulaError::BrokenReference->value);
});

test('оформление переезжает вместе с содержимым', function (): void {
    $context = sheetContext();

    writeCells($context, ['A3' => 'Продукты']);

    test()->withToken($context['token'])->patchJson("/api/sheets/{$context['sheet']}/cells/format", [
        'range' => ['startRow' => 3, 'startColumn' => 1, 'endRow' => 3, 'endColumn' => 1],
        'format' => ['background' => '#FFFF00'],
    ])->assertOk();

    test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/rows/insert", ['row' => 3])
        ->assertOk();

    $content = test()->withToken($context['token'])
        ->getJson("/api/sheets/{$context['sheet']}/cells")
        ->json();

    expect(formatAt($content, 'A4'))->toBe(['background' => '#FFFF00'])
        ->and(formatAt($content, 'A3'))->toBeEmpty();
});

test('содержимое не выталкивается за последнюю строку', function (): void {
    $context = sheetContext();

    writeCells($context, ['A200' => 'Итого']);

    test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/rows/insert", ['row' => 2])
        ->assertStatus(409);

    $content = test()->withToken($context['token'])
        ->getJson("/api/sheets/{$context['sheet']}/cells")
        ->json();

    expect(valueAt($content, 'A200'))->toBe('Итого');
});

test('вставка колонки переселяет содержимое вправо и сдвигает ссылки', function (): void {
    $context = sheetContext();

    writeCells($context, ['A1' => 'Статья', 'B1' => '120', 'C1' => '80', 'E1' => '=СУММ(B1:C1)']);

    test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/columns/insert", ['column' => 2])
        ->assertOk();

    $content = test()->withToken($context['token'])
        ->getJson("/api/sheets/{$context['sheet']}/cells")
        ->json();

    expect(valueAt($content, 'A1'))->toBe('Статья')
        ->and(valueAt($content, 'B1'))->toBeNull()
        ->and(valueAt($content, 'C1'))->toBe('120')
        ->and(inputAt($content, 'F1'))->toBe('=СУММ(C1:D1)')
        ->and(valueAt($content, 'F1'))->toBe('200');
});

test('удаление колонки сдвигает правые и ломает ссылку на удалённую', function (): void {
    $context = sheetContext();

    writeCells($context, ['B1' => '120', 'C1' => '80', 'D1' => '=C1*2', 'A5' => '=B1+1']);

    test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/columns/delete", ['column' => 2])
        ->assertOk();

    $content = test()->withToken($context['token'])
        ->getJson("/api/sheets/{$context['sheet']}/cells")
        ->json();

    expect(valueAt($content, 'B1'))->toBe('80')
        ->and(inputAt($content, 'C1'))->toBe('=B1*2')
        ->and(valueAt($content, 'C1'))->toBe('160')
        ->and(valueAt($content, 'A5'))->toBe(FormulaError::BrokenReference->value);
});

test('ширины переезжают вместе с колонками', function (): void {
    $context = sheetContext();

    test()->withToken($context['token'])
        ->patchJson("/api/sheets/{$context['sheet']}", ['columnWidths' => ['A' => 200, 'B' => 90, 'C' => 150]])
        ->assertOk();

    test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/columns/delete", ['column' => 2])
        ->assertOk()
        ->assertJsonPath('columnWidths', ['A' => 200, 'B' => 150]);

    test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/columns/insert", ['column' => 1])
        ->assertOk()
        ->assertJsonPath('columnWidths', ['B' => 200, 'C' => 150]);
});

test('содержимое не выталкивается за последнюю колонку', function (): void {
    $context = sheetContext();

    writeCells($context, [CellReference::columnToLetters(SheetModel::DEFAULT_COLUMN_COUNT) . '1' => 'Итого']);

    test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/columns/insert", ['column' => 1])
        ->assertStatus(409);
});
