<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Data;

use Finance\Domains\Cells\Enums\CellKind;
use Finance\FormulaEngine\Dependencies\CellDependencies;
use Finance\FormulaEngine\Values\CellRange;

/**
 * Содержимое ячейки в том виде, в каком оно ложится в базу после ввода.
 *
 * Собирается только именованными конструкторами: каждый вид содержимого
 * заполняет свой набор полей, и число с текстом одновременно получить нельзя.
 * Итог формулы сюда не входит — он появляется позже, при пересчёте.
 */
final readonly class CellContent
{
    /**
     * @param list<string>    $dependsOnCells
     * @param list<CellRange> $dependsOnRanges
     */
    private function __construct(
        public CellKind $kind,
        public ?string $input,
        public ?string $numberValue = null,
        public ?string $textValue = null,
        public array $dependsOnCells = [],
        public array $dependsOnRanges = [],
    ) {
    }

    public static function empty(): self
    {
        return new self(CellKind::Empty, null);
    }

    /**
     * @param string $numericString число, уже приведённое к записи с точкой
     */
    public static function number(string $input, string $numericString): self
    {
        return new self(CellKind::Number, $input, numberValue: $numericString);
    }

    public static function text(string $input): self
    {
        return new self(CellKind::Text, $input, textValue: $input);
    }

    public static function formula(string $input, CellDependencies $dependencies): self
    {
        return new self(
            CellKind::Formula,
            $input,
            dependsOnCells: $dependencies->referenceKeys(),
            dependsOnRanges: $dependencies->ranges,
        );
    }
}
