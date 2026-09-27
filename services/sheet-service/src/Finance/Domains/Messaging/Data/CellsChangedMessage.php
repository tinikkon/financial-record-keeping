<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Data;

use Carbon\CarbonImmutable;
use Finance\Domains\Cells\Data\AppliedCellEdits;
use Finance\Domains\Cells\Resources\CellResource;
use Finance\Domains\Messaging\Contracts\OutgoingMessage;
use Finance\Domains\Sheets\Models\SheetModel;

/**
 * Сообщение сервису истории о том, что на листе изменились ячейки.
 *
 * Название листа и книга едут вместе с ячейками: сервис истории не ходит
 * за ними обратно, журнал и сводки должны строиться только из очереди.
 */
final readonly class CellsChangedMessage implements OutgoingMessage
{
    public function __construct(
        private string $routingKey,
        private string $messageIdentifier,
        public CarbonImmutable $occurredAt,
        public SheetModel $sheet,
        public AppliedCellEdits $changes,
        public string $actorIdentifier,
    ) {
    }

    public function routingKey(): string
    {
        return $this->routingKey;
    }

    public function messageIdentifier(): string
    {
        return $this->messageIdentifier;
    }

    public function body(): array
    {
        return [
            'messageId' => $this->messageIdentifier,
            'occurredAt' => $this->occurredAt->toIso8601String(),
            'workbookId' => $this->sheet->workbook_id,
            'sheetId' => $this->sheet->identifier(),
            'sheetName' => $this->sheet->name,
            'sheetVersion' => $this->changes->sheetVersion,
            'actorId' => $this->actorIdentifier,
            'cells' => array_map(CellResource::toArray(...), $this->changes->cells),
        ];
    }
}
