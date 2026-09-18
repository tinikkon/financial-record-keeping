<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Exceptions;

use Finance\Domains\Core\Exceptions\DomainException;
use Throwable;

final class EmailAlreadyTakenException extends DomainException
{
    public function __construct(string $email, ?Throwable $previous = null)
    {
        parent::__construct("Пользователь с почтой {$email} уже заведён", 409, $previous);
    }
}
