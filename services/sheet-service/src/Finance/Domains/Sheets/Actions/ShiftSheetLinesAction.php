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
use Finance\Domains\Sheets\Exceptions\CellsWouldOverflowSheetException;
use Finance\Domains\Sheets\Models\SheetModel;
use Finance\Domains\Sheets\Contracts\SheetRepositoryContract;
use Finance\FormulaEngine\Editing\ReferenceShiftRewriter;
use Finance\FormulaEngine\Editing\ShiftAxis;
use Finance\FormulaEngine\Exceptions\InvalidReferenceException;
use Finance\FormulaEngine\Exceptions\SyntaxErrorException;
use Finance\FormulaEngine\Values\CellReference;
use Finance\FormulaEngine\Values\FormulaError;

/**
 * Вставляет и удаляет строки или колонки, переселяя содержимое и переписывая ссылки.
 *
 * Перестройка выражается обычной пачкой правок: содержимое переезжает на новые
 * адреса, освободившееся очищается. Поэтому бесплатно получаются и пересчёт,
 * и рассылка второму устройству, и запись в журнал — всё то же, что у правки
 * руками.
 *
 * Оформление переносится отдельно: в журнал оно и так не попадает, а правка
 * содержимого его не трогает. Так же отдельно переезжают ширины колонок:
 * они принадлежат листу, а не ячейкам.
 */
final readonly class ShiftSheetLinesAction
{
    public function __construct(
        private CellRepositoryContract $cells,
        private SheetRepositoryContract $sheets,
        private ReferenceShiftRewriter $rewriter,
        private ApplyCellEditsAction $applyEdits,
    ) {
    }

    /**
     * @throws CellsWouldOverflowSheetException
     * @throws InvalidFormulaException
     * @throws InvalidReferenceException
     * @throws SyntaxErrorException
     * @throws CellNotSavedException
     */
    public function insert(
        SheetModel $sheet,
        ShiftAxis $axis,
        int $at,
        int $count,
        string $userIdentifier,
    ): AppliedCellEdits {
        return $this->shift(
            $sheet,
            $axis,
            $userIdentifier,
            static fn (int $line): int => $line >= $at ? $line + $count : $line,
            fn (string $formula): string => $this->rewriter->afterInsert($formula, $axis, $at, $count),
        );
    }

    /**
     * @throws CellsWouldOverflowSheetException
     * @throws InvalidFormulaException
     * @throws InvalidReferenceException
     * @throws SyntaxErrorException
     * @throws CellNotSavedException
     */
    public function delete(
        SheetModel $sheet,
        ShiftAxis $axis,
        int $from,
        int $count,
        string $userIdentifier,
    ): AppliedCellEdits {
        $afterBand = $from + $count;

        return $this->shift(
            $sheet,
            $axis,
            $userIdentifier,
            static function (int $line) use ($from, $afterBand, $count): ?int {
                if ($line < $from) {
                    return $line;
                }

                return $line >= $afterBand ? $line - $count : null;
            },
            fn (string $formula): ?string => $this->rewriter->afterDelete($formula, $axis, $from, $count),
        );
    }

    /**
     * @param callable(int): ?int       $newLineOf   новая строка или колонка ячейки, null — удалена
     * @param callable(string): ?string $rewriteOf   формула после перестройки, null — опоры больше нет
     *
     * @throws CellsWouldOverflowSheetException
     * @throws InvalidFormulaException
     * @throws InvalidReferenceException
     * @throws SyntaxErrorException
     * @throws CellNotSavedException
     */
    private function shift(
        SheetModel $sheet,
        ShiftAxis $axis,
        string $userIdentifier,
        callable $newLineOf,
        callable $rewriteOf,
    ): AppliedCellEdits {
        $sheetIdentifier = $sheet->identifier();
        $lastLine = $axis === ShiftAxis::Rows ? $sheet->row_count : $sheet->column_count;
        $inputsByAddress = [];
        $formatsByAddress = [];
        $touchedAddresses = [];

        foreach ($this->cells->forSheet($sheetIdentifier) as $cell) {
            $current = new CellReference($cell->column, $cell->row);
            $touchedAddresses[$current->key()] = true;

            $newLine = $newLineOf($axis->coordinateOf($current));

            if ($newLine === null) {
                continue;
            }

            if ($newLine > $lastLine) {
                throw new CellsWouldOverflowSheetException();
            }

            $moved = $axis->withCoordinate($current, $newLine);
            $touchedAddresses[$moved->key()] = true;
            $inputsByAddress[$moved->key()] = $this->movedInput($cell, $rewriteOf);

            if ($cell->format !== []) {
                $formatsByAddress[$moved->key()] = $cell->format;
            }
        }

        $this->moveFormats($sheetIdentifier, array_keys($touchedAddresses), $formatsByAddress);

        if ($axis === ShiftAxis::Columns) {
            $this->moveColumnWidths($sheet, $newLineOf);
        }

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
     * Ширины переезжают вместе с колонками, ширина удалённой пропадает.
     * Модель листа обновляется тут же, чтобы ответ показал новые ширины.
     *
     * @param callable(int): ?int $newLineOf
     */
    private function moveColumnWidths(SheetModel $sheet, callable $newLineOf): void
    {
        $widths = [];

        foreach ($sheet->column_widths ?? [] as $letters => $width) {
            $newColumn = $newLineOf(CellReference::lettersToColumn((string) $letters));

            if ($newColumn !== null) {
                $widths[CellReference::columnToLetters($newColumn)] = $width;
            }
        }

        $this->sheets->updateColumnWidths($sheet->identifier(), $widths);
        $sheet->column_widths = $widths;
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
