<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Services;

use RuntimeException;
use Throwable;

final class MessagePublishingFailedException extends RuntimeException
{
    public function __construct(string $routingKey, ?Throwable $previous = null)
    {
        parent::__construct("Не удалось опубликовать сообщение с ключом {$routingKey}", 0, $previous);
    }
}
