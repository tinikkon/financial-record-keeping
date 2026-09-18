<?php

declare(strict_types=1);

test('новый месяц копирует подписи, формулы и оформление, но не числа', function (): void {
    $context = sheetContext();
    writeCells($context, [
        'A3' => 'Продукты',
        'B3' => '120',
        'B4' => '80',
        'B91' => '=СУММ(B3:B90)',
    ]);

    test()->withToken($context['token'])->patchJson("/api/sheets/{$context['sheet']}/cells/format", [
        'range' => ['startRow' => 91, 'startColumn' => 2, 'endRow' => 91, 'endColumn' => 2],
        'format' => ['background' => '#FFFF00'],
    ]);

    $copy = test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/duplicate", ['name' => '11.26'])
        ->assertCreated()
        ->json();

    $content = test()->withToken($context['token'])->getJson("/api/sheets/{$copy['id']}/cells")->json();

    expect(valueAt($content, 'A3'))->toBe('Продукты')
        ->and(valueAt($content, 'B3'))->toBeNull()
        ->and(valueAt($content, 'B91'))->toBe('0');

    $total = collect($content['cells'])->firstWhere('address', 'B91');
    expect($total['input'])->toBe('=СУММ(B3:B90)')
        ->and($total['format']['background'])->toBe('#FFFF00');
});

test('копия месяца живёт своей жизнью и не тянет правки исходного', function (): void {
    $context = sheetContext();
    writeCells($context, ['B3' => '120', 'B91' => '=СУММ(B3:B90)']);

    $copy = test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/duplicate", ['name' => '11.26'])
        ->json();

    writeCells($context, ['B3' => '500']);

    $content = test()->withToken($context['token'])->getJson("/api/sheets/{$copy['id']}/cells")->json();

    expect(valueAt($content, 'B91'))->toBe('0');
});

test('месяц с занятым названием не заводится', function (): void {
    $context = sheetContext();

    test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/duplicate", ['name' => '10.26'])
        ->assertStatus(409);
});

test('удаление листа убирает и его ячейки', function (): void {
    $context = sheetContext();
    writeCells($context, ['B3' => '120']);

    test()->withToken($context['token'])->deleteJson("/api/sheets/{$context['sheet']}")->assertNoContent();

    /** @var \Finance\Domains\Cells\Contracts\CellRepositoryContract $cells */
    $cells = app(\Finance\Domains\Cells\Contracts\CellRepositoryContract::class);

    expect($cells->forSheet($context['sheet']))->toHaveCount(0);
});
