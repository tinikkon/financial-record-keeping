<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Exceptions;

use Finance\FormulaEngine\Values\FormulaError;
use Throwable;

/**
 * Внутренний способ прервать вычисление и донести ошибку до верхнего уровня.
 * Наружу из движка не выходит: вычислитель превращает её в значение ячейки.
 */
final class EvaluationFailedException extends FormulaEngineException
{
    public function __construct(
        private readonly FormulaError $error,
        ?Throwable $previous = null,
    ) {
        parent::__construct($error->value, 0, $previous);
    }

    public function error(): FormulaError
    {
        return $this->error;
    }
}
