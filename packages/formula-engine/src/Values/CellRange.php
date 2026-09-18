<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Values;

use Finance\FormulaEngine\Exceptions\InvalidReferenceException;

/**
 * Прямоугольный диапазон ячеек. Границы приводятся к нормальному виду, поэтому
 * B90:B3 и B3:B90 — один и тот же диапазон.
 */
final readonly class CellRange
{
    public int $minimumColumn;

    public int $maximumColumn;

    public int $minimumRow;

    public int $maximumRow;

    public function __construct(
        public CellReference $start,
        public CellReference $end,
    ) {
        $this->minimumColumn = min($start->column, $end->column);
        $this->maximumColumn = max($start->column, $end->column);
        $this->minimumRow = min($start->row, $end->row);
        $this->maximumRow = max($start->row, $end->row);
    }

    /**
     * @throws InvalidReferenceException
     */
    public static function fromString(string $range): self
    {
        $sides = explode(':', trim($range));

        if (count($sides) !== 2) {
            throw new InvalidReferenceException("Не похоже на диапазон: {$range}");
        }

        return new self(CellReference::fromString($sides[0]), CellReference::fromString($sides[1]));
    }

    public function contains(CellReference $reference): bool
    {
        return $reference->column >= $this->minimumColumn
            && $reference->column <= $this->maximumColumn
            && $reference->row >= $this->minimumRow
            && $reference->row <= $this->maximumRow;
    }

    public function cellCount(): int
    {
        return ($this->maximumColumn - $this->minimumColumn + 1)
            * ($this->maximumRow - $this->minimumRow + 1);
    }

    /**
     * @return list<CellReference>
     *
     * @throws InvalidReferenceException
     */
    public function references(): array
    {
        $references = [];

        for ($row = $this->minimumRow; $row <= $this->maximumRow; $row++) {
            for ($column = $this->minimumColumn; $column <= $this->maximumColumn; $column++) {
                $references[] = new CellReference($column, $row);
            }
        }

        return $references;
    }

    public function toString(): string
    {
        return $this->start->toString() . ':' . $this->end->toString();
    }
}
