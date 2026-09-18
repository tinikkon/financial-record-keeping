<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Values;

use Finance\FormulaEngine\Exceptions\InvalidReferenceException;

/**
 * Ссылка на ячейку: колонка и строка, каждая из которых может быть закреплена
 * знаком доллара. Закреплённая координата не сдвигается при копировании формулы.
 */
final readonly class CellReference
{
    public const int MAXIMUM_COLUMN = 16384;

    public const int MAXIMUM_ROW = 1048576;

    /**
     * @param int $column номер колонки, начиная с единицы: A равно 1
     * @param int $row    номер строки, начиная с единицы
     *
     * @throws InvalidReferenceException
     */
    public function __construct(
        public int $column,
        public int $row,
        public bool $columnFixed = false,
        public bool $rowFixed = false,
    ) {
        if ($column < 1 || $column > self::MAXIMUM_COLUMN) {
            throw new InvalidReferenceException("Номер колонки вне допустимых границ: {$column}");
        }

        if ($row < 1 || $row > self::MAXIMUM_ROW) {
            throw new InvalidReferenceException("Номер строки вне допустимых границ: {$row}");
        }
    }

    /**
     * @throws InvalidReferenceException
     */
    public static function fromString(string $reference): self
    {
        $matched = preg_match('/^(\$?)([A-Za-z]{1,3})(\$?)(\d{1,7})$/', trim($reference), $parts);

        if ($matched !== 1) {
            throw new InvalidReferenceException("Не похоже на ссылку на ячейку: {$reference}");
        }

        return new self(
            column: self::lettersToColumn($parts[2]),
            row: (int) $parts[4],
            columnFixed: $parts[1] === '$',
            rowFixed: $parts[3] === '$',
        );
    }

    /**
     * Ссылка в том виде, в каком она записана в формуле, вместе с закреплением.
     */
    public function toString(): string
    {
        return ($this->columnFixed ? '$' : '')
            . self::columnToLetters($this->column)
            . ($this->rowFixed ? '$' : '')
            . $this->row;
    }

    /**
     * Адрес ячейки без признаков закрепления. Используется как ключ: A1 и $A$1 —
     * одна и та же ячейка, и в графе зависимостей они не должны раздваиваться.
     */
    public function key(): string
    {
        return self::columnToLetters($this->column) . $this->row;
    }

    public function equals(self $other): bool
    {
        return $this->column === $other->column && $this->row === $other->row;
    }

    /**
     * Сдвиг при копировании формулы. Закреплённые координаты остаются на месте.
     *
     * @throws InvalidReferenceException если ссылка уезжает за границы листа
     */
    public function shifted(int $rowDelta, int $columnDelta): self
    {
        return new self(
            column: $this->columnFixed ? $this->column : $this->column + $columnDelta,
            row: $this->rowFixed ? $this->row : $this->row + $rowDelta,
            columnFixed: $this->columnFixed,
            rowFixed: $this->rowFixed,
        );
    }

    public static function columnToLetters(int $column): string
    {
        $letters = '';

        while ($column > 0) {
            $remainder = ($column - 1) % 26;
            $letters = chr(ord('A') + $remainder) . $letters;
            $column = intdiv($column - 1 - $remainder, 26);
        }

        return $letters;
    }

    public static function lettersToColumn(string $letters): int
    {
        $column = 0;

        foreach (str_split(strtoupper($letters)) as $letter) {
            $column = $column * 26 + (ord($letter) - ord('A') + 1);
        }

        return $column;
    }
}
