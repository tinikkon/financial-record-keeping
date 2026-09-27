<?php

declare(strict_types=1);

namespace Finance\Domains\Realtime\Actions;

use Finance\Domains\Cells\Data\AppliedCellEdits;
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
    public function execute(string $sheetIdentifier, AppliedCellEdits $changes, string $actorIdentifier): void
    {
        try {
            CellsChangedEvent::dispatch($sheetIdentifier, $changes, $actorIdentifier);
        } catch (Throwable $exception) {
            Log::warning('Не удалось разослать изменения листа', [
                'sheetId' => $sheetIdentifier,
                'sheetVersion' => $changes->sheetVersion,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
