<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Exceptions;

use Finance\Domains\Core\Exceptions\DomainException;
use Throwable;

final class InvalidRefreshTokenException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Сессия истекла, войдите заново', 401, $previous);
    }
}
