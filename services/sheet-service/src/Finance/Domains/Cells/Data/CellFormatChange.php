<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Data;

use Finance\Domains\Cells\Enums\CellFormatProperty;

/**
 * Правка оформления: что наложить и что снять.
 *
 * Снятие описано отдельным списком, а не значением null в оформлении: иначе
 * «свойство не прислали» и «свойство просят убрать» были бы неразличимы, и убрать
 * заливку, не сбросив заодно жирность, стало бы невозможно.
 */
final readonly class CellFormatChange
{
    /**
     * @param list<CellFormatProperty> $cleared
     */
    public function __construct(
        public CellFormat $applied,
        public array $cleared = [],
    ) {
    }

    public function applyTo(CellFormat $current): CellFormat
    {
        return new CellFormat(
            background: $this->resolve(CellFormatProperty::Background, $this->applied->background, $current->background),
            bold: $this->resolve(CellFormatProperty::Bold, $this->applied->bold, $current->bold),
            italic: $this->resolve(CellFormatProperty::Italic, $this->applied->italic, $current->italic),
            align: $this->resolve(CellFormatProperty::Align, $this->applied->align, $current->align),
            decimals: $this->resolve(CellFormatProperty::Decimals, $this->applied->decimals, $current->decimals),
        );
    }

    /**
     * @template T
     *
     * @param T|null $applied
     * @param T|null $current
     *
     * @return T|null
     */
    private function resolve(CellFormatProperty $property, mixed $applied, mixed $current): mixed
    {
        if (in_array($property, $this->cleared, true)) {
            return null;
        }

        return $applied ?? $current;
    }
}
