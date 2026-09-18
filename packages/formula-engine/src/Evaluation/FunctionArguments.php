<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Evaluation;

use Finance\FormulaEngine\Values\CellValue;

/**
 * Аргументы вызова функции, уже приведённые к спискам значений.
 *
 * Каждый аргумент — список: отдельное выражение даёт список из одного значения,
 * диапазон — список из всех его ячеек. Функции благодаря этому не знают,
 * пришло к ним число или диапазон.
 */
final readonly class FunctionArguments
{
    /**
     * @param list<list<CellValue>> $arguments
     */
    public function __construct(private array $arguments)
    {
    }

    public function count(): int
    {
        return count($this->arguments);
    }

    /**
     * @return list<CellValue> все значения всех аргументов подряд
     */
    public function flattened(): array
    {
        return array_merge(...$this->arguments);
    }
}
