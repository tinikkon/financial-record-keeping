<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Actions;

use Finance\Domains\Calculation\Actions\ApplyCellEditsAction;
use Finance\Domains\Cells\Actions\AppliedCellEdits;
use Finance\Domains\Cells\Actions\CellEdit;
use Finance\Domains\Cells\Contracts\CellRepositoryContract;
use Finance\Domains\Cells\Enums\CellKind;
use Finance\Domains\Cells\Exceptions\CellNotSavedException;
use Finance\Domains\Cells\Exceptions\InvalidFormulaException;
use Finance\Domains\Cells\Models\CellModel;
use Finance\Domains\Sheets\Exceptions\RowsWouldOverflowSheetException;
use Finance\Domains\Sheets\Models\SheetModel;
use Finance\FormulaEngine\Editing\RowShiftRewriter;
use Finance\FormulaEngine\Exceptions\InvalidReferenceException;
use Finance\FormulaEngine\Exceptions\SyntaxErrorException;
use Finance\FormulaEngine\Values\CellReference;
use Finance\FormulaEngine\Values\FormulaError;

/**
 * Вставляет и удаляет строки, переселяя содержимое и переписывая ссылки.
 *
 * Перестройка выражается обычной пачкой правок: содержимое переезжает на новые
 * адреса, освободившееся очищается. Поэтому бесплатно получаются и пересчёт,
 * и рассылка второму устройству, и запись в журнал — всё то же, что у правки
 * руками.
 *
 * Оформление переносится отдельно: в журнал оно и так не попадает, а правка
 * содержимого его не трогает.
 */
final readonly class ShiftSheetRowsAction
{
    public function __construct(
        private CellRepositoryContract $cells,
        private RowShiftRewriter $rewriter,
        private ApplyCellEditsAction $applyEdits,
    ) {
    }

    /**
     * @throws RowsWouldOverflowSheetException
     * @throws InvalidFormulaException
     * @throws InvalidReferenceException
     * @throws SyntaxErrorException
     * @throws CellNotSavedException
     */
    public function insert(SheetModel $sheet, int $atRow, int $count, string $userIdentifier): AppliedCellEdits
    {
        return $this->shift(
            $sheet,
            $userIdentifier,
            static fn (int $row): int => $row >= $atRow ? $row + $count : $row,
            fn (string $formula): string => $this->rewriter->afterInsert($formula, $atRow, $count),
        );
    }

    /**
     * @throws RowsWouldOverflowSheetException
     * @throws InvalidFormulaException
     * @throws InvalidReferenceException
     * @throws SyntaxErrorException
     * @throws CellNotSavedException
     */
    public function delete(SheetModel $sheet, int $fromRow, int $count, string $userIdentifier): AppliedCellEdits
    {
        $afterBand = $fromRow + $count;

        return $this->shift(
            $sheet,
            $userIdentifier,
            static function (int $row) use ($fromRow, $afterBand, $count): ?int {
                if ($row < $fromRow) {
                    return $row;
                }

                return $row >= $afterBand ? $row - $count : null;
            },
            fn (string $formula): ?string => $this->rewriter->afterDelete($formula, $fromRow, $count),
        );
    }

    /**
     * @param callable(int): ?int       $newRowOf    новая строка ячейки, null — строка удалена
     * @param callable(string): ?string $rewriteOf   формула после перестройки, null — опоры больше нет
     *
     * @throws RowsWouldOverflowSheetException
     * @throws InvalidFormulaException
     * @throws InvalidReferenceException
     * @throws SyntaxErrorException
     * @throws CellNotSavedException
     */
    private function shift(
        SheetModel $sheet,
        string $userIdentifier,
        callable $newRowOf,
        callable $rewriteOf,
    ): AppliedCellEdits {
        $sheetIdentifier = $sheet->identifier();
        $inputsByAddress = [];
        $formatsByAddress = [];
        $touchedAddresses = [];

        foreach ($this->cells->forSheet($sheetIdentifier) as $cell) {
            $current = new CellReference($cell->column, $cell->row);
            $touchedAddresses[$current->key()] = true;

            $newRow = $newRowOf($cell->row);

            if ($newRow === null) {
                continue;
            }

            if ($newRow > $sheet->row_count) {
                throw new RowsWouldOverflowSheetException();
            }

            $moved = new CellReference($cell->column, $newRow);
            $touchedAddresses[$moved->key()] = true;
            $inputsByAddress[$moved->key()] = $this->movedInput($cell, $rewriteOf);

            if ($cell->format !== []) {
                $formatsByAddress[$moved->key()] = $cell->format;
            }
        }

        $this->moveFormats($sheetIdentifier, array_keys($touchedAddresses), $formatsByAddress);

        $edits = [];

        foreach (array_keys($touchedAddresses) as $address) {
            $edits[] = new CellEdit(CellReference::fromString($address), $inputsByAddress[$address] ?? null);
        }

        return $this->applyEdits->execute($sheet, $edits, $userIdentifier);
    }

    /**
     * Содержимое ячейки на новом месте.
     *
     * Формула, потерявшая опору, превращается в текст ошибки: движок такую
     * ссылку не разберёт, а молча оставить прежнюю — значит показывать число,
     * посчитанное по исчезнувшим строкам.
     *
     * @param callable(string): ?string $rewriteOf
     */
    private function movedInput(CellModel $cell, callable $rewriteOf): ?string
    {
        if ($cell->input === null || $cell->cellKind() !== CellKind::Formula) {
            return $cell->input;
        }

        return $rewriteOf($cell->input) ?? FormulaError::BrokenReference->value;
    }

    /**
     * @param list<string>                      $addresses
     * @param array<string, array<string, mixed>> $formatsByAddress
     *
     * @throws InvalidReferenceException
     * @throws CellNotSavedException
     */
    private function moveFormats(string $sheetIdentifier, array $addresses, array $formatsByAddress): void
    {
        foreach ($addresses as $address) {
            $this->cells->save($sheetIdentifier, CellReference::fromString($address), [
                'format' => $formatsByAddress[$address] ?? null,
            ]);
        }
    }
}
