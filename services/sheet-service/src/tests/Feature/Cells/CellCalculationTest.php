<?php

declare(strict_types=1);

use Finance\Domains\Workbooks\Actions\CreateWorkbookAction;

/**
 * Готовит вход и пустой лист, как при первом открытии нового месяца.
 *
 * @return array{token: string, userId: string, workbook: string, sheet: string}
 */
function sheetContext(): array
{
    createUser();

    $tokens = test()->postJson('/api/auth/login', [
        'email' => 'kostya@example.com',
        'password' => 'finance-local-8',
    ])->json();

    /** @var CreateWorkbookAction $createWorkbook */
    $createWorkbook = app(CreateWorkbookAction::class);
    $workbook = $createWorkbook->execute('План финансов', $tokens['user']['id'])->identifier();

    $sheet = test()->withToken($tokens['accessToken'])
        ->postJson("/api/workbooks/{$workbook}/sheets", ['name' => '10.26'])
        ->json('id');

    return [
        'token' => $tokens['accessToken'],
        'userId' => $tokens['user']['id'],
        'workbook' => $workbook,
        'sheet' => $sheet,
    ];
}

/**
 * @param array<string, string|null> $cellsByAddress адрес ячейки к вводу
 */
function writeCells(array $context, array $cellsByAddress): array
{
    $edits = [];

    foreach ($cellsByAddress as $address => $input) {
        preg_match('/^([A-Z]+)(\d+)$/', $address, $parts);

        $edits[] = [
            'column' => \Finance\FormulaEngine\Values\CellReference::lettersToColumn($parts[1]),
            'row' => (int) $parts[2],
            'input' => $input,
        ];
    }

    return test()->withToken($context['token'])
        ->patchJson("/api/sheets/{$context['sheet']}/cells", ['edits' => $edits])
        ->assertOk()
        ->json();
}

function valueAt(array $response, string $address): ?string
{
    foreach ($response['cells'] as $cell) {
        if ($cell['address'] === $address) {
            return $cell['value'] ?? $cell['error'];
        }
    }

    return null;
}

test('число и подпись сохраняются каждое в своём виде', function (): void {
    $context = sheetContext();

    $response = writeCells($context, ['A3' => 'Продукты', 'B3' => '120']);

    expect(valueAt($response, 'A3'))->toBe('Продукты')
        ->and(valueAt($response, 'B3'))->toBe('120');
});

test('число с запятой принимается как число', function (): void {
    $context = sheetContext();

    $response = writeCells($context, ['B3' => '120,50']);

    expect(valueAt($response, 'B3'))->toBe('120.5');
});

test('формула суммы считает колонку', function (): void {
    $context = sheetContext();
    writeCells($context, ['B3' => '120', 'B4' => '80', 'B5' => '50']);

    $response = writeCells($context, ['B91' => '=СУММ(B3:B90)']);

    expect(valueAt($response, 'B91'))->toBe('250');
});

test('правка слагаемого пересчитывает итог тем же ответом', function (): void {
    $context = sheetContext();
    writeCells($context, ['B3' => '120', 'B91' => '=СУММ(B3:B90)']);

    $response = writeCells($context, ['B4' => '80']);

    expect(valueAt($response, 'B4'))->toBe('80')
        ->and(valueAt($response, 'B91'))->toBe('200');
});

test('пересчёт идёт по всей цепочке до остатка', function (): void {
    $context = sheetContext();
    writeCells($context, [
        'F2' => '0,03485535',
        'C3' => '3500',
        'C91' => '=СУММ(C3:C90)',
        'D94' => '=C91*$F$2',
    ]);

    $response = writeCells($context, ['C4' => '1500']);

    expect(valueAt($response, 'C91'))->toBe('5000')
        ->and(valueAt($response, 'D94'))->toBe('174.27675');
});

test('очистка ячейки пересчитывает итог', function (): void {
    $context = sheetContext();
    writeCells($context, ['B3' => '120', 'B4' => '80', 'B91' => '=СУММ(B3:B90)']);

    $response = writeCells($context, ['B4' => null]);

    expect(valueAt($response, 'B91'))->toBe('120');
});

test('кольцо ссылок не роняет запрос, а помечает ячейки ошибкой', function (): void {
    $context = sheetContext();

    $response = writeCells($context, ['A1' => '=A2+1', 'A2' => '=A1+1']);

    expect(valueAt($response, 'A1'))->toBe('#ЦИКЛ!')
        ->and(valueAt($response, 'A2'))->toBe('#ЦИКЛ!');
});

test('деление на ноль показывается ошибкой в ячейке', function (): void {
    $context = sheetContext();

    $response = writeCells($context, ['B3' => '0', 'C3' => '=100/B3']);

    expect(valueAt($response, 'C3'))->toBe('#ДЕЛ/0!');
});

test('сломанная формула не применяется и прежнее значение сохраняется', function (): void {
    $context = sheetContext();
    writeCells($context, ['B3' => '120']);

    test()->withToken($context['token'])
        ->patchJson("/api/sheets/{$context['sheet']}/cells", [
            'edits' => [['row' => 3, 'column' => 2, 'input' => '=СУММ(B3:B90']],
        ])
        ->assertStatus(422);

    $sheet = test()->withToken($context['token'])->getJson("/api/sheets/{$context['sheet']}/cells")->json();

    expect(valueAt($sheet, 'B3'))->toBe('120');
});

test('версия листа растёт с каждой применённой пачкой правок', function (): void {
    $context = sheetContext();

    $first = writeCells($context, ['B3' => '120']);
    $second = writeCells($context, ['B4' => '80']);

    expect($first['sheetVersion'])->toBe(1)
        ->and($second['sheetVersion'])->toBe(2);
});

test('лист отдаётся целиком со всеми заполненными ячейками', function (): void {
    $context = sheetContext();
    writeCells($context, ['A3' => 'Продукты', 'B3' => '120', 'B91' => '=СУММ(B3:B90)']);

    $response = test()->withToken($context['token'])
        ->getJson("/api/sheets/{$context['sheet']}/cells")
        ->assertOk()
        ->json();

    expect($response['cells'])->toHaveCount(3)
        ->and($response['sheet']['version'])->toBe(1);
});
