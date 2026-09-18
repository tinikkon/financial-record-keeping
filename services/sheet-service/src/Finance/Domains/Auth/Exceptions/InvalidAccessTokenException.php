<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Exceptions;

use Finance\Domains\Core\Exceptions\DomainException;
use Throwable;

final class InvalidAccessTokenException extends DomainException
{
    public function __construct(string $message = 'Требуется вход', ?Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
