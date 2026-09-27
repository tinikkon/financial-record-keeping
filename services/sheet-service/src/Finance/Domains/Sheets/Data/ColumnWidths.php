<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Data;

use Finance\FormulaEngine\Values\CellReference;

/**
 * Ширины колонок листа по буквам колонок.
 *
 * Хранятся только заданные вручную: остальные колонки клиент рисует
 * шириной по умолчанию. Объект неизменяемый — каждая операция возвращает новый.
 */
final readonly class ColumnWidths
{
    /**
     * @param array<string, int> $widthsByColumn
     */
    public function __construct(private array $widthsByColumn = [])
    {
    }

    /**
     * @param array<array-key, mixed> $stored
     */
    public static function fromArray(array $stored): self
    {
        $widths = [];
        foreach ($stored as $column => $width) {
            if (is_numeric($width)) {
                $widths[(string) $column] = (int) $width;
            }
        }

        return new self($widths);
    }

    /**
     * Заданные в правке ширины заменяют прежние, остальные остаются как были.
     */
    public function merge(self $changes): self
    {
        return new self([...$this->widthsByColumn, ...$changes->widthsByColumn]);
    }

    public function clamped(int $minimum, int $maximum): self
    {
        return new self(array_map(
            static fn (int $width): int => max($minimum, min($maximum, $width)),
            $this->widthsByColumn,
        ));
    }

    /**
     * Ширины переезжают вместе со своими колонками; ширина удалённой колонки пропадает.
     *
     * @param callable(int): ?int $newColumnOf новый номер колонки, null — колонка удалена
     */
    public function remapped(callable $newColumnOf): self
    {
        $widths = [];
        foreach ($this->widthsByColumn as $letters => $width) {
            $newColumn = $newColumnOf(CellReference::lettersToColumn((string) $letters));
            if ($newColumn !== null) {
                $widths[CellReference::columnToLetters($newColumn)] = $width;
            }
        }

        return new self($widths);
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return $this->widthsByColumn;
    }
}
