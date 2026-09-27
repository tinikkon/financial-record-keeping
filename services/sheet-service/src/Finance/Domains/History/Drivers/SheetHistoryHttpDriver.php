<?php

declare(strict_types=1);

namespace Finance\Domains\History\Drivers;

use Finance\Domains\History\Contracts\SheetHistoryDriverContract;
use Finance\Domains\History\Data\SheetState;
use Finance\Domains\History\Exceptions\HistoryUnavailableException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Обращение к сервису истории по HTTP.
 *
 * Токен пользователя передаётся как есть: сервис истории проверит подпись сам
 * открытым ключом, и заводить отдельный служебный доступ между сервисами
 * не требуется.
 */
final readonly class SheetHistoryHttpDriver implements SheetHistoryDriverContract
{
    public function __construct(
        private string $baseUrl,
        private int $timeoutSeconds,
    ) {
    }

    public function stateAtVersion(string $sheetIdentifier, int $version, string $accessToken): SheetState
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout($this->timeoutSeconds)
                ->acceptJson()
                ->get("{$this->baseUrl}/api/sheets/{$sheetIdentifier}/state", ['version' => $version]);
        } catch (ConnectionException $exception) {
            throw new HistoryUnavailableException($exception);
        }

        if (! $response->successful()) {
            throw new HistoryUnavailableException();
        }

        $cells = $response->json('cells', []);

        return SheetState::fromArray(is_array($cells) ? $cells : []);
    }
}
