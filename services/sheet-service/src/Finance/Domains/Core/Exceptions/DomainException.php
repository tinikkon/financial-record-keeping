<?php

declare(strict_types=1);

namespace Finance\Domains\Core\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Предок исключений предметной области. Несёт код ответа и понятное
 * пользователю сообщение — обработчик превращает его в ответ API.
 */
abstract class DomainException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 400,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
