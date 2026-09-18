<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Exceptions;

use Finance\Domains\Core\Exceptions\DomainException;
use Throwable;

final class InvalidCredentialsException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Неверная почта или пароль', 401, $previous);
    }
}
