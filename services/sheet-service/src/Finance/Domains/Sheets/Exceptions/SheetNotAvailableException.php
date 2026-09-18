<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Exceptions;

use Finance\Domains\Core\Exceptions\DomainException;
use Throwable;

final class SheetNotAvailableException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Лист не найден', 404, $previous);
    }
}
