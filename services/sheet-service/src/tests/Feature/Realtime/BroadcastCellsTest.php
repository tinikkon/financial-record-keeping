<?php

declare(strict_types=1);

use Finance\Domains\Realtime\Events\CellsChangedEvent;
use Illuminate\Support\Facades\Event;

test('правка ячеек уходит событием в канал листа', function (): void {
    $context = sheetContext();
    Event::fake([CellsChangedEvent::class]);

    writeCells($context, ['B3' => '120', 'B91' => '=СУММ(B3:B90)']);

    Event::assertDispatched(
        CellsChangedEvent::class,
        fn (CellsChangedEvent $event): bool => $event->sheetIdentifier === $context['sheet']
            && $event->sheetVersion === 1
            && count($event->cells) === 2,
    );
});

test('событие уходит в закрытый канал именно этого листа', function (): void {
    $context = sheetContext();

    $event = new CellsChangedEvent($context['sheet'], 7, [], $context['userId']);

    expect($event->broadcastOn()[0]->name)->toBe("private-sheet.{$context['sheet']}")
        ->and($event->broadcastAs())->toBe('cells.changed')
        ->and($event->broadcastWith())->toMatchArray(['sheetVersion' => 7, 'actorId' => $context['userId']]);
});

test('оформление тоже рассылается', function (): void {
    $context = sheetContext();
    Event::fake([CellsChangedEvent::class]);

    test()->withToken($context['token'])->patchJson("/api/sheets/{$context['sheet']}/cells/format", [
        'range' => ['startRow' => 94, 'startColumn' => 4, 'endRow' => 94, 'endColumn' => 4],
        'format' => ['background' => '#FFFF00'],
    ])->assertOk();

    Event::assertDispatched(CellsChangedEvent::class);
});

test('участник книги допускается в канал листа', function (): void {
    $context = sheetContext();

    // Проверяется именно успех: «не 401» пропускало запрет доступа с кодом 403,
    // и подписка на канал молча не работала.
    test()->postJson('/api/broadcasting/auth', [
        'channel_name' => "private-sheet.{$context['sheet']}",
        'socket_id' => '1234.5678',
    ], ['Authorization' => "Bearer {$context['token']}"])->assertOk();
});

test('посторонний в канал чужого листа не допускается', function (): void {
    $context = sheetContext();

    createUser('stranger@example.com');
    $strangerToken = test()->postJson('/api/auth/login', [
        'email' => 'stranger@example.com',
        'password' => 'finance-local-8',
    ])->json('accessToken');

    test()->postJson('/api/broadcasting/auth', [
        'channel_name' => "private-sheet.{$context['sheet']}",
        'socket_id' => '1234.5678',
    ], ['Authorization' => "Bearer {$strangerToken}"])->assertForbidden();
});
