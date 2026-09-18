<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Tests\Support;

use Brick\Math\BigDecimal;
use Finance\FormulaEngine\Contracts\CellValueResolver;
use Finance\FormulaEngine\Values\CellRange;
use Finance\FormulaEngine\Values\CellReference;
use Finance\FormulaEngine\Values\CellValue;

/**
 * Лист в памяти. Заменяет обращение к базе в тестах движка.
 */
final class ArrayCellValueResolver implements CellValueResolver
{
    /** @var array<string, CellValue> */
    private array $cells = [];

    /**
     * @param array<string, string|int|float|CellValue> $cells адрес ячейки к значению
     */
    public function __construct(array $cells = [])
    {
        foreach ($cells as $address => $value) {
            $this->cells[$address] = $value instanceof CellValue
                ? $value
                : CellValue::number(BigDecimal::of((string) $value));
        }
    }

    public function withText(string $address, string $text): self
    {
        $this->cells[$address] = CellValue::text($text);

        return $this;
    }

    public function valueAt(CellReference $reference): CellValue
    {
        return $this->cells[$reference->key()] ?? CellValue::blank();
    }

    public function valuesIn(CellRange $range): array
    {
        return array_map(
            fn (CellReference $reference): CellValue => $this->valueAt($reference),
            $range->references(),
        );
    }
}
