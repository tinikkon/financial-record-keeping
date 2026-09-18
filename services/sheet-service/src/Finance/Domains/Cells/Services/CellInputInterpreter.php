<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Services;

use Finance\Domains\Cells\Enums\CellKind;

/**
 * Определяет, чем является введённое пользователем содержимое ячейки.
 *
 * Запятая в числе принимается: на телефоне запятая стоит на числовой клавиатуре,
 * и требовать точку было бы издевательством. В формулах запятая остаётся
 * разделителем аргументов, поэтому там такого послабления нет.
 */
final readonly class CellInputInterpreter
{
    public function kindOf(?string $input): CellKind
    {
        if ($input === null || trim($input) === '') {
            return CellKind::Empty;
        }

        if (str_starts_with(ltrim($input), '=')) {
            return CellKind::Formula;
        }

        return is_numeric($this->toNumericString($input)) ? CellKind::Number : CellKind::Text;
    }

    public function toNumericString(string $input): string
    {
        return str_replace([' ', ','], ['', '.'], trim($input));
    }
}
