<?php

declare(strict_types=1);

namespace Finance\Domains\History\Exceptions;

use Finance\Domains\Core\Exceptions\DomainException;
use Throwable;

/**
 * Сервис истории не ответил.
 *
 * Это единственное место, где работа с таблицей зависит от соседнего сервиса,
 * и зависимость честная: восстановить прошлое состояние без журнала нельзя.
 * Всё остальное продолжает работать.
 */
final class HistoryUnavailableException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('История сейчас недоступна, попробуйте позже', 503, $previous);
    }
}
