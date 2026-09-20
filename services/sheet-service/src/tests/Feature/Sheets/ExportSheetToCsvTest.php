<?php

declare(strict_types=1);

test('выгрузка отдаёт посчитанные значения, а не формулы', function (): void {
    $context = sheetContext();

    writeCells($context, [
        'A2' => 'назначение',
        'B2' => 'расход; бел',
        'A3' => 'Продукты',
        'B3' => '248.60',
        'B4' => '=СУММ(B3:B3)',
    ]);

    $csv = test()->withToken($context['token'])
        ->get("/api/sheets/{$context['sheet']}/csv")
        ->assertOk()
        ->content();

    $lines = explode("\r\n", trim($csv, "\u{FEFF}\r\n"));

    expect($lines[0])->toBe(';')
        ->and($lines[1])->toBe('назначение;"расход; бел"')
        ->and($lines[2])->toBe('Продукты;248,6')
        ->and($lines[3])->toBe(';248,6');
});

test('выгрузка начинается с метки порядка байтов и зовётся именем листа', function (): void {
    $context = sheetContext();

    writeCells($context, ['A1' => 'Продукты']);

    $response = test()->withToken($context['token'])
        ->get("/api/sheets/{$context['sheet']}/csv")
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=utf-8');

    expect($response->content())->toStartWith("\u{FEFF}")
        ->and($response->headers->get('Content-Disposition'))->toContain('10.26.csv');
});

test('чужой лист не выгружается', function (): void {
    $context = sheetContext();

    createUser('stranger@example.com');

    $strangerToken = test()->postJson('/api/auth/login', [
        'email' => 'stranger@example.com',
        'password' => 'finance-local-8',
    ])->json('accessToken');

    test()->withToken($strangerToken)
        ->get("/api/sheets/{$context['sheet']}/csv")
        ->assertStatus(404);
});
