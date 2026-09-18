<?php

declare(strict_types=1);

namespace Finance\Domains\History\Contracts;

use Finance\Domains\History\Exceptions\HistoryUnavailableException;

interface SheetHistoryDriverContract
{
    /**
     * Содержимое листа на указанную версию: адрес ячейки к тому, что в ней было.
     *
     * @return array<string, array{value: string|null, input: string|null}>
     *
     * @throws HistoryUnavailableException
     */
    public function stateAtVersion(string $sheetIdentifier, int $version, string $accessToken): array;
}
