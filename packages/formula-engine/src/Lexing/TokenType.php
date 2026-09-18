<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Lexing;

enum TokenType
{
    case Number;
    case Text;
    case Identifier;
    case CellReference;
    case Plus;
    case Minus;
    case Asterisk;
    case Slash;
    case Caret;
    case OpeningParenthesis;
    case ClosingParenthesis;
    case ArgumentSeparator;
    case Colon;
    case EndOfInput;
}
