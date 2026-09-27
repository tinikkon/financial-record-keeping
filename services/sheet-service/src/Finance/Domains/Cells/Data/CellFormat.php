<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Data;

use Finance\Domains\Cells\Enums\CellAlignment;
use Finance\Domains\Cells\Enums\CellFormatProperty;

/**
 * Оформление ячейки. Незаданное свойство — null: ячейка показывается как обычно.
 *
 * Из базы читается снисходительно: значение неподходящего типа отбрасывается,
 * а не роняет чтение листа. Оформление — украшение, и из-за испорченной заливки
 * пользователь не должен терять доступ к своим числам.
 */
final readonly class CellFormat
{
    public function __construct(
        public ?string $background = null,
        public ?bool $bold = null,
        public ?bool $italic = null,
        public ?CellAlignment $align = null,
        public ?int $decimals = null,
    ) {
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        $background = $stored[CellFormatProperty::Background->value] ?? null;
        $bold = $stored[CellFormatProperty::Bold->value] ?? null;
        $italic = $stored[CellFormatProperty::Italic->value] ?? null;
        $align = $stored[CellFormatProperty::Align->value] ?? null;
        $decimals = $stored[CellFormatProperty::Decimals->value] ?? null;

        return new self(
            background: is_string($background) ? $background : null,
            bold: is_bool($bold) ? $bold : null,
            italic: is_bool($italic) ? $italic : null,
            align: is_string($align) ? CellAlignment::tryFrom($align) : null,
            decimals: is_int($decimals) ? $decimals : null,
        );
    }

    public function isEmpty(): bool
    {
        return $this->toArray() === [];
    }

    /**
     * Только заданные свойства: пустое оформление в базе и в ответе — пустой объект.
     *
     * @return array<string, string|bool|int>
     */
    public function toArray(): array
    {
        return array_filter(
            [
                CellFormatProperty::Background->value => $this->background,
                CellFormatProperty::Bold->value => $this->bold,
                CellFormatProperty::Italic->value => $this->italic,
                CellFormatProperty::Align->value => $this->align?->value,
                CellFormatProperty::Decimals->value => $this->decimals,
            ],
            static fn (string|bool|int|null $value): bool => $value !== null,
        );
    }
}
