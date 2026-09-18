<?php

declare(strict_types=1);

namespace Finance\Domains\Core\Support;

/**
 * Перевод номера колонки в буквенный адрес.
 *
 * Свой, а не взятый из движка формул: сервису истории движок не нужен, и тянуть
 * его целиком ради одного преобразования значило бы связать сервисы на пустом месте.
 */
final readonly class ColumnLetters
{
    public static function fromNumber(int $column): string
    {
        $letters = '';
        $remaining = $column;

        while ($remaining > 0) {
            $remainder = ($remaining - 1) % 26;
            $letters = chr(ord('A') + $remainder) . $letters;
            $remaining = intdiv($remaining - 1 - $remainder, 26);
        }

        return $letters;
    }
}
