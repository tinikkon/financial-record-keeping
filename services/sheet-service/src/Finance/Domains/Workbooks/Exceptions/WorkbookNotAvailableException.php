<?php

declare(strict_types=1);

namespace Finance\Domains\Workbooks\Exceptions;

use Finance\Domains\Core\Exceptions\DomainException;
use Throwable;

/**
 * Книга не найдена либо не принадлежит пользователю.
 *
 * Оба случая дают один и тот же ответ намеренно: иначе по коду ответа можно
 * было бы выяснить, какие книги существуют у других людей.
 */
final class WorkbookNotAvailableException extends DomainException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Книга не найдена', 404, $previous);
    }
}
