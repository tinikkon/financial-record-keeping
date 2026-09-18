<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Exceptions;

use Throwable;

/**
 * Формулу не удалось разобрать. Такую формулу не сохраняют: пользователю
 * возвращается ошибка ввода, а прежнее содержимое ячейки остаётся нетронутым.
 */
final class SyntaxErrorException extends FormulaEngineException
{
    public function __construct(
        string $message,
        private readonly int $position = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function position(): int
    {
        return $this->position;
    }
}
