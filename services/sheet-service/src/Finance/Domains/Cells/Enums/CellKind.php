<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Enums;

/**
 * Что записано в ячейке.
 *
 * Различие между числом и текстом хранится явно, а не выводится из значения:
 * иначе номер счёта «007» превратился бы в семёрку.
 */
enum CellKind: string
{
    case Number = 'number';
    case Text = 'text';
    case Formula = 'formula';
    case Empty = 'empty';
}
