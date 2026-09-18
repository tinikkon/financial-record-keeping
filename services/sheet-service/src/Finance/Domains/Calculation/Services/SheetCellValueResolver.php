<?php

declare(strict_types=1);

namespace Finance\Domains\Calculation\Services;

use Finance\Domains\Cells\Contracts\CellRepositoryContract;
use Finance\Domains\Cells\Enums\CellKind;
use Finance\Domains\Cells\Models\CellModel;
use Finance\FormulaEngine\Contracts\CellValueResolver;
use Finance\FormulaEngine\Values\CellRange;
use Finance\FormulaEngine\Values\CellReference;
use Finance\FormulaEngine\Values\CellValue;
use Finance\FormulaEngine\Values\FormulaError;

/**
 * Мост между листом в базе и движком формул.
 *
 * Значения, посчитанные в текущем пересчёте, держатся в памяти и отдаются вместо
 * того, что лежит в базе: иначе следующая по цепочке формула читала бы устаревшее
 * значение предыдущей.
 */
final class SheetCellValueResolver implements CellValueResolver
{
    /** @var array<string, CellValue> */
    private array $computed = [];

    public function __construct(
        private readonly CellRepositoryContract $cells,
        private readonly string $sheetIdentifier,
    ) {
    }

    public function remember(CellReference $reference, CellValue $value): void
    {
        $this->computed[$reference->key()] = $value;
    }

    public function valueAt(CellReference $reference): CellValue
    {
        if (isset($this->computed[$reference->key()])) {
            return $this->computed[$reference->key()];
        }

        $cell = $this->cells->findAt($this->sheetIdentifier, $reference);

        return $cell === null ? CellValue::blank() : self::toCellValue($cell);
    }

    public function valuesIn(CellRange $range): array
    {
        $storedByAddress = [];

        foreach ($this->cells->findInRange($this->sheetIdentifier, $range) as $cell) {
            $storedByAddress[$cell->address()] = self::toCellValue($cell);
        }

        $values = [];

        foreach ($range->references() as $reference) {
            $values[] = $this->computed[$reference->key()]
                ?? $storedByAddress[$reference->key()]
                ?? CellValue::blank();
        }

        return $values;
    }

    public static function toCellValue(CellModel $cell): CellValue
    {
        if ($cell->error !== null) {
            $error = FormulaError::tryFrom($cell->error);

            return $error === null ? CellValue::blank() : CellValue::error($error);
        }

        if ($cell->cellKind() === CellKind::Text) {
            return CellValue::text((string) $cell->value_text);
        }

        $number = $cell->value_number;

        return $number === null ? CellValue::blank() : CellValue::number($number);
    }
}
