<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Lexing;

final readonly class Token
{
    public function __construct(
        public TokenType $type,
        public string $lexeme,
        public int $position,
    ) {
    }

    public function is(TokenType ...$types): bool
    {
        return in_array($this->type, $types, true);
    }
}
