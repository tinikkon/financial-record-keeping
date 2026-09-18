<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Actions;

use Carbon\CarbonImmutable;
use Finance\Domains\Cells\Models\CellModel;
use Finance\Domains\Cells\Resources\CellResource;
use Finance\Domains\Messaging\Contracts\EventPublisherContract;
use Finance\Domains\Messaging\Contracts\PendingMessageRepositoryContract;
use Finance\Domains\Messaging\Services\MessagePublishingFailedException;
use Finance\Domains\Sheets\Models\SheetModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Отправляет сервису истории сведения о том, что изменилось.
 *
 * Вызывается после завершённой транзакции, а не внутри неё: сообщение об изменении,
 * которое потом откатилось, породило бы в журнале запись о несуществовавшей правке.
 */
final readonly class PublishCellsChangedAction
{
    public function __construct(
        private EventPublisherContract $publisher,
        private PendingMessageRepositoryContract $pendingMessages,
    ) {
    }

    /**
     * @param list<CellModel> $cells
     */
    public function execute(SheetModel $sheet, int $sheetVersion, array $cells, string $actorIdentifier): void
    {
        $routingKey = (string) config('messaging.routing_keys.cells_changed');
        $messageIdentifier = (string) Str::uuid();

        $payload = [
            'messageId' => $messageIdentifier,
            'occurredAt' => CarbonImmutable::now()->toIso8601String(),
            'workbookId' => $sheet->workbook_id,
            'sheetId' => $sheet->identifier(),
            'sheetName' => $sheet->name,
            'sheetVersion' => $sheetVersion,
            'actorId' => $actorIdentifier,
            'cells' => array_map(CellResource::toArray(...), $cells),
        ];

        try {
            $this->publisher->publish($routingKey, $messageIdentifier, $payload);
        } catch (MessagePublishingFailedException $exception) {
            Log::warning('Событие не ушло в очередь, отложено до восстановления', [
                'routingKey' => $routingKey,
                'messageId' => $messageIdentifier,
                'exception' => $exception->getMessage(),
            ]);

            $this->pendingMessages->store($routingKey, $messageIdentifier, $payload);
        }
    }
}
