<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Values;

/**
 * Ошибки, которые попадают в ячейку вместо значения.
 *
 * Значения перечисления совпадают с тем, что видит пользователь в таблице.
 */
enum FormulaError: string
{
    case DivisionByZero = '#ДЕЛ/0!';
    case CircularReference = '#ЦИКЛ!';
    case BrokenReference = '#ССЫЛКА!';
    case WrongValueType = '#ЗНАЧ!';
    case UnknownName = '#ИМЯ?';
}
