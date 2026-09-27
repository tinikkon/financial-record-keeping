<?php

declare(strict_types=1);

namespace Finance\Domains\History\Contracts;

use Finance\Domains\History\Data\SheetState;
use Finance\Domains\History\Exceptions\HistoryUnavailableException;

interface SheetHistoryDriverContract
{
    /**
     * Содержимое листа на указанную версию.
     *
     * @throws HistoryUnavailableException
     */
    public function stateAtVersion(string $sheetIdentifier, int $version, string $accessToken): SheetState;
}
