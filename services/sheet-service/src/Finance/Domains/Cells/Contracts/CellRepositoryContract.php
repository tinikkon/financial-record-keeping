<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Contracts;

use Finance\Domains\Cells\Models\CellModel;
use Finance\FormulaEngine\Values\CellRange;
use Finance\FormulaEngine\Values\CellReference;
use Illuminate\Support\Collection;

interface CellRepositoryContract
{
    /**
     * Все заполненные ячейки листа. Пустые не хранятся вовсе: лист на двести
     * строк и двадцать шесть колонок — это пять тысяч ячеек, из которых заняты
     * десятки, и хранить пустоту незачем.
     *
     * @return Collection<int, CellModel>
     */
    public function forSheet(string $sheetIdentifier): Collection;

    public function findAt(string $sheetIdentifier, CellReference $reference): ?CellModel;

    /**
     * @return Collection<int, CellModel>
     */
    public function findInRange(string $sheetIdentifier, CellRange $range): Collection;

    /**
     * Ячейки, чьи формулы прямо ссылаются на указанную — как отдельной ссылкой,
     * так и диапазоном, накрывающим её.
     *
     * @return Collection<int, CellModel>
     */
    public function dependentsOf(string $sheetIdentifier, CellReference $reference): Collection;

    /**
     * @param array<string, mixed> $attributes
     */
    public function save(string $sheetIdentifier, CellReference $reference, array $attributes): CellModel;

    public function deleteForSheet(string $sheetIdentifier): void;
}
