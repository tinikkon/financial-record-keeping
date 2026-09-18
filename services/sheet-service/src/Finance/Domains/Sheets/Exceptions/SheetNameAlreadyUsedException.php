<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Exceptions;

use Finance\Domains\Core\Exceptions\DomainException;
use Throwable;

final class SheetNameAlreadyUsedException extends DomainException
{
    public function __construct(string $name, ?Throwable $previous = null)
    {
        parent::__construct("Лист с названием «{$name}» в этой книге уже есть", 409, $previous);
    }
}
