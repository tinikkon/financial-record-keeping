<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Exceptions;

use Finance\Domains\Core\Exceptions\DomainException;
use Throwable;

final class CellsWouldOverflowSheetException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Заполненные ячейки не помещаются на листе: сдвигать их некуда', 409, $previous);
    }
}
