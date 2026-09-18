<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Exceptions;

use Throwable;

/**
 * Ссылка на ячейку не имеет смысла: номер строки или колонки вне допустимых границ.
 */
final class InvalidReferenceException extends FormulaEngineException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
