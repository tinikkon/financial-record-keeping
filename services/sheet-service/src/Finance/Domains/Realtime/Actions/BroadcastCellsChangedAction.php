<?php

declare(strict_types=1);

namespace Finance\Domains\Realtime\Actions;

use Finance\Domains\Cells\Models\CellModel;
use Finance\Domains\Cells\Resources\CellResource;
use Finance\Domains\Realtime\Events\CellsChangedEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Рассылает изменения по листу.
 *
 * Сбой рассылки не должен ронять правку: данные уже записаны, и отказ вернуть
 * пользователю успех из-за упавшего WebSocket был бы хуже, чем временная
 * потеря живого обновления. Клиент в этом случае догонит состояние сам —
 * по разрыву в номерах версий или при следующем открытии листа.
 */
final readonly class BroadcastCellsChangedAction
{
    /**
     * @param list<CellModel> $cells
     */
    public function execute(string $sheetIdentifier, int $sheetVersion, array $cells, string $actorIdentifier): void
    {
        try {
            CellsChangedEvent::dispatch(
                $sheetIdentifier,
                $sheetVersion,
                array_map(CellResource::toArray(...), $cells),
                $actorIdentifier,
            );
        } catch (Throwable $exception) {
            Log::warning('Не удалось разослать изменения листа', [
                'sheetId' => $sheetIdentifier,
                'sheetVersion' => $sheetVersion,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
