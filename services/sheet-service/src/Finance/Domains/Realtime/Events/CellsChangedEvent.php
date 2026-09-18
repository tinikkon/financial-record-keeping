<?php

declare(strict_types=1);

namespace Finance\Domains\Realtime\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Пачка изменившихся ячеек, уходящая в открытые вкладки.
 *
 * Отправляется сразу, а не через очередь заданий: смысл события в том, чтобы
 * правка появилась у второго человека мгновенно, и складывать его в очередь
 * означало бы добавить задержку ради ничего.
 */
final readonly class CellsChangedEvent implements ShouldBroadcastNow
{
    use Dispatchable;

    /**
     * @param list<array<string, mixed>> $cells
     */
    public function __construct(
        public string $sheetIdentifier,
        public int $sheetVersion,
        public array $cells,
        public string $actorIdentifier,
    ) {
    }

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("sheet.{$this->sheetIdentifier}")];
    }

    public function broadcastAs(): string
    {
        return 'cells.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'sheetVersion' => $this->sheetVersion,
            'cells' => $this->cells,
            // Автор правки нужен клиенту, чтобы не применять повторно то,
            // что он уже отобразил из ответа на собственный запрос.
            'actorId' => $this->actorIdentifier,
        ];
    }
}
