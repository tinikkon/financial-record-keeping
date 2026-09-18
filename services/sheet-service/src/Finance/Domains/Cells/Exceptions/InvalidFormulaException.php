<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Exceptions;

use Finance\Domains\Core\Exceptions\DomainException;
use Throwable;

/**
 * Формулу не удалось разобрать.
 *
 * Такая правка не применяется целиком: в ячейке остаётся прежнее содержимое,
 * а пользователь видит, что именно не так. Сохранять заведомо сломанную формулу
 * и показывать ошибку в ячейке было бы хуже — потерялось бы прежнее значение.
 */
final class InvalidFormulaException extends DomainException
{
    public function __construct(string $address, string $reason, ?Throwable $previous = null)
    {
        parent::__construct("Ошибка в формуле ячейки {$address}: {$reason}", 422, $previous);
    }
}
