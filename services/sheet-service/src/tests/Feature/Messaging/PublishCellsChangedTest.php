<?php

declare(strict_types=1);

use Finance\Domains\Messaging\Contracts\EventPublisherContract;
use Finance\Domains\Messaging\Models\PendingMessageModel;
use Finance\Domains\Messaging\Services\MessagePublishingFailedException;

/**
 * Публикатор, запоминающий отправленное. Заменяет RabbitMQ: проверяется поведение
 * приложения, а не работоспособность брокера.
 */
final class RecordingEventPublisher implements EventPublisherContract
{
    /** @var list<array{routingKey: string, messageId: string, payload: array<string, mixed>}> */
    public array $published = [];

    public function publish(string $routingKey, string $messageIdentifier, array $payload): void
    {
        $this->published[] = ['routingKey' => $routingKey, 'messageId' => $messageIdentifier, 'payload' => $payload];
    }
}

final class BrokenEventPublisher implements EventPublisherContract
{
    public function publish(string $routingKey, string $messageIdentifier, array $payload): void
    {
        throw new MessagePublishingFailedException($routingKey);
    }
}

test('правка публикует событие с ключом и полным составом', function (): void {
    $publisher = new RecordingEventPublisher();
    app()->instance(EventPublisherContract::class, $publisher);

    $context = sheetContext();
    writeCells($context, ['A3' => 'Продукты', 'B3' => '120']);

    expect($publisher->published)->toHaveCount(1);

    $message = $publisher->published[0];

    expect($message['routingKey'])->toBe('sheet.cells.changed')
        ->and($message['payload']['sheetId'])->toBe($context['sheet'])
        ->and($message['payload']['sheetName'])->toBe('10.26')
        ->and($message['payload']['sheetVersion'])->toBe(1)
        ->and($message['payload']['actorId'])->toBe($context['userId'])
        ->and($message['payload']['cells'])->toHaveCount(2)
        ->and($message['payload']['messageId'])->toBe($message['messageId']);
});

test('каждое событие получает свой идентификатор', function (): void {
    $publisher = new RecordingEventPublisher();
    app()->instance(EventPublisherContract::class, $publisher);

    $context = sheetContext();
    writeCells($context, ['B3' => '120']);
    writeCells($context, ['B4' => '80']);

    $identifiers = array_column($publisher->published, 'messageId');

    expect($identifiers)->toHaveCount(2)
        ->and(array_unique($identifiers))->toHaveCount(2);
});

test('недоступная очередь не ломает правку таблицы', function (): void {
    app()->instance(EventPublisherContract::class, new BrokenEventPublisher());

    $context = sheetContext();
    $response = writeCells($context, ['B3' => '120', 'B91' => '=СУММ(B3:B90)']);

    expect(valueAt($response, 'B91'))->toBe('120');
});

test('неотправленное складывается для досылки', function (): void {
    app()->instance(EventPublisherContract::class, new BrokenEventPublisher());

    $context = sheetContext();
    writeCells($context, ['B3' => '120']);

    $pending = PendingMessageModel::query()->first();

    expect($pending)->not->toBeNull()
        ->and($pending->routing_key)->toBe('sheet.cells.changed')
        ->and($pending->payload['sheetId'])->toBe($context['sheet'])
        ->and($pending->attempts)->toBe(0);
});

test('команда досылки отправляет отложенное и очищает хранилище', function (): void {
    app()->instance(EventPublisherContract::class, new BrokenEventPublisher());

    $context = sheetContext();
    writeCells($context, ['B3' => '120']);

    $publisher = new RecordingEventPublisher();
    app()->instance(EventPublisherContract::class, $publisher);

    $this->artisan('finance:resend-pending-messages')->assertSuccessful();

    expect($publisher->published)->toHaveCount(1)
        ->and(PendingMessageModel::query()->count())->toBe(0);
});

test('при всё ещё лежащей очереди досылка считает попытку и не теряет сообщение', function (): void {
    app()->instance(EventPublisherContract::class, new BrokenEventPublisher());

    $context = sheetContext();
    writeCells($context, ['B3' => '120']);

    $this->artisan('finance:resend-pending-messages')->assertFailed();

    $pending = PendingMessageModel::query()->first();

    expect($pending)->not->toBeNull()
        ->and($pending->attempts)->toBe(1);
});
