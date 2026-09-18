<?php

declare(strict_types=1);

use Finance\Domains\History\Contracts\SheetHistoryDriverContract;
use Finance\Domains\History\Exceptions\HistoryUnavailableException;

/**
 * Подменяет сервис истории заранее известным состоянием.
 */
final class FakeSheetHistoryDriver implements SheetHistoryDriverContract
{
    /**
     * @param array<string, array{value: string|null, input: string|null}> $state
     */
    public function __construct(private readonly array $state)
    {
    }

    public function stateAtVersion(string $sheetIdentifier, int $version, string $accessToken): array
    {
        return $this->state;
    }
}

final class BrokenSheetHistoryDriver implements SheetHistoryDriverContract
{
    public function stateAtVersion(string $sheetIdentifier, int $version, string $accessToken): array
    {
        throw new HistoryUnavailableException();
    }
}

test('восстановление возвращает прежние значения и пересчитывает формулы', function (): void {
    $context = sheetContext();
    writeCells($context, ['B3' => '120', 'B91' => '=СУММ(B3:B90)']);
    writeCells($context, ['B3' => '999']);

    app()->instance(SheetHistoryDriverContract::class, new FakeSheetHistoryDriver([
        'B3' => ['value' => '120', 'input' => '120'],
        'B91' => ['value' => '120', 'input' => '=СУММ(B3:B90)'],
    ]));

    $response = test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/restore", ['version' => 1])
        ->assertOk()
        ->json();

    expect(valueAt($response, 'B3'))->toBe('120')
        ->and(valueAt($response, 'B91'))->toBe('120');
});

test('ячейки, появившиеся после восстанавливаемой версии, очищаются', function (): void {
    $context = sheetContext();
    writeCells($context, ['B3' => '120']);
    writeCells($context, ['B4' => '80']);

    app()->instance(SheetHistoryDriverContract::class, new FakeSheetHistoryDriver([
        'B3' => ['value' => '120', 'input' => '120'],
    ]));

    test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/restore", ['version' => 1])
        ->assertOk();

    $лист = test()->withToken($context['token'])->getJson("/api/sheets/{$context['sheet']}/cells")->json();

    expect(valueAt($лист, 'B3'))->toBe('120')
        ->and(valueAt($лист, 'B4'))->toBeNull();
});

test('восстановление само становится новой версией, а не откатывает счётчик', function (): void {
    $context = sheetContext();
    writeCells($context, ['B3' => '120']);
    writeCells($context, ['B3' => '999']);

    app()->instance(SheetHistoryDriverContract::class, new FakeSheetHistoryDriver([
        'B3' => ['value' => '120', 'input' => '120'],
    ]));

    $response = test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/restore", ['version' => 1])
        ->json();

    expect($response['sheetVersion'])->toBe(3);
});

test('недоступная история сообщает об этом понятно и не трогает лист', function (): void {
    $context = sheetContext();
    writeCells($context, ['B3' => '120']);

    app()->instance(SheetHistoryDriverContract::class, new BrokenSheetHistoryDriver());

    test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/restore", ['version' => 1])
        ->assertStatus(503)
        ->assertJsonPath('message', 'История сейчас недоступна, попробуйте позже');

    $лист = test()->withToken($context['token'])->getJson("/api/sheets/{$context['sheet']}/cells")->json();

    expect(valueAt($лист, 'B3'))->toBe('120');
});

test('без указания версии восстановление отвергается', function (): void {
    $context = sheetContext();

    test()->withToken($context['token'])
        ->postJson("/api/sheets/{$context['sheet']}/restore", [])
        ->assertStatus(422);
});
