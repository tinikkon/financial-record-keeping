<?php

declare(strict_types=1);

namespace Finance\Domains\Calculation\Actions;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Finance\Domains\Calculation\Services\SheetCellValueResolver;
use Finance\Domains\Calculation\Services\SheetDependencyGraph;
use Finance\Domains\Cells\Contracts\CellRepositoryContract;
use Finance\Domains\Cells\Data\AppliedCellEdits;
use Finance\Domains\Cells\Data\CellContent;
use Finance\Domains\Cells\Data\CellEdit;
use Finance\Domains\Cells\Exceptions\CellNotSavedException;
use Finance\Domains\Cells\Enums\CellKind;
use Finance\Domains\Cells\Exceptions\InvalidFormulaException;
use Finance\Domains\Cells\Models\CellModel;
use Finance\Domains\Cells\Services\CellInputInterpreter;
use Finance\Domains\Sheets\Contracts\SheetRepositoryContract;
use Finance\Domains\Sheets\Models\SheetModel;
use Finance\FormulaEngine\Ast\Node;
use Finance\FormulaEngine\Exceptions\SyntaxErrorException;
use Finance\FormulaEngine\FormulaEngine;
use Finance\FormulaEngine\Recalculation\RecalculationPlanner;
use Finance\FormulaEngine\Values\CellReference;
use Finance\FormulaEngine\Values\CellValue;
use Finance\FormulaEngine\Values\FormulaError;
use Illuminate\Support\Facades\DB;

/**
 * Применяет пачку правок и пересчитывает всё, на что они повлияли.
 *
 * Порядок важен: сначала в базу ложится новое содержимое ячеек вместе с их
 * зависимостями, и только потом строится план пересчёта. Иначе только что
 * введённая формула не попала бы в граф и её итог остался бы старым.
 */
final readonly class ApplyCellEditsAction
{
    public function __construct(
        private CellRepositoryContract $cells,
        private SheetRepositoryContract $sheets,
        private CellInputInterpreter $interpreter,
        private FormulaEngine $engine,
        private RecalculationPlanner $planner,
    ) {
    }

    /**
     * @param list<CellEdit> $edits
     *
     * @throws InvalidFormulaException
     */
    public function execute(SheetModel $sheet, array $edits, string $userIdentifier): AppliedCellEdits
    {
        // Формулы разбираются до записи: одна сломанная отменяет всю пачку,
        // и прежнее содержимое ячеек остаётся нетронутым.
        $parsedFormulas = $this->parseFormulas($edits);

        /** @var AppliedCellEdits $result */
        $result = DB::connection('mongodb')->transaction(
            fn (): AppliedCellEdits => $this->apply($sheet, $edits, $parsedFormulas, $userIdentifier),
        );

        return $result;
    }

    /**
     * @param list<CellEdit> $edits
     *
     * @return array<string, Node>
     *
     * @throws InvalidFormulaException
     */
    private function parseFormulas(array $edits): array
    {
        $parsed = [];

        foreach ($edits as $edit) {
            if ($this->interpreter->kindOf($edit->input) !== CellKind::Formula) {
                continue;
            }

            try {
                $parsed[$edit->reference->key()] = $this->engine->parse((string) $edit->input);
            } catch (SyntaxErrorException $exception) {
                throw new InvalidFormulaException($edit->reference->key(), $exception->getMessage(), $exception);
            }
        }

        return $parsed;
    }

    /**
     * @param list<CellEdit>     $edits
     * @param array<string, Node> $parsedFormulas
     */
    private function apply(SheetModel $sheet, array $edits, array $parsedFormulas, string $userIdentifier): AppliedCellEdits
    {
        $sheetIdentifier = $sheet->identifier();
        $resolver = new SheetCellValueResolver($this->cells, $sheetIdentifier);
        $changedReferences = [];

        foreach ($edits as $edit) {
            $this->writeInput($sheetIdentifier, $edit, $parsedFormulas, $userIdentifier, $resolver);
            $changedReferences[] = $edit->reference;
        }

        $plan = $this->planner->plan($changedReferences, new SheetDependencyGraph($this->cells, $sheetIdentifier));

        /** @var array<string, CellModel> $touched */
        $touched = [];

        foreach ($plan->order as $reference) {
            $recalculated = $this->recalculate($sheetIdentifier, $reference, $resolver, $userIdentifier);

            if ($recalculated !== null) {
                $touched[$reference->key()] = $recalculated;
            }
        }

        foreach ($plan->circularReferences as $reference) {
            $touched[$reference->key()] = $this->cells->saveComputedValue(
                $sheetIdentifier,
                $reference,
                CellValue::error(FormulaError::CircularReference),
                $userIdentifier,
            );
        }

        foreach ($edits as $edit) {
            if (! isset($touched[$edit->reference->key()])) {
                $stored = $this->cells->findAt($sheetIdentifier, $edit->reference);

                if ($stored !== null) {
                    $touched[$edit->reference->key()] = $stored;
                }
            }
        }

        return new AppliedCellEdits(
            sheetVersion: $this->sheets->incrementVersion($sheetIdentifier),
            cells: array_values($touched),
        );
    }

    /**
     * @param array<string, Node> $parsedFormulas
     */
    private function writeInput(
        string $sheetIdentifier,
        CellEdit $edit,
        array $parsedFormulas,
        string $userIdentifier,
        SheetCellValueResolver $resolver,
    ): void {
        $input = (string) $edit->input;

        $content = match ($this->interpreter->kindOf($edit->input)) {
            CellKind::Empty => CellContent::empty(),
            CellKind::Number => CellContent::number($input, $this->interpreter->toNumericString($input)),
            CellKind::Text => CellContent::text($input),
            CellKind::Formula => CellContent::formula(
                $input,
                $this->engine->dependenciesOfNode($parsedFormulas[$edit->reference->key()]),
            ),
        };

        // Значение числа и текста известно сразу, и следующие формулы пачки
        // должны видеть именно его. Итог формулы появится только при пересчёте.
        $immediateValue = $this->immediateValueOf($content);
        if ($immediateValue !== null) {
            $resolver->remember($edit->reference, $immediateValue);
        }

        $this->cells->saveContent($sheetIdentifier, $edit->reference, $content, $userIdentifier);
    }

    private function immediateValueOf(CellContent $content): ?CellValue
    {
        return match ($content->kind) {
            CellKind::Empty => CellValue::blank(),
            CellKind::Number => $this->numberValue((string) $content->numberValue),
            CellKind::Text => CellValue::text((string) $content->textValue),
            CellKind::Formula => null,
        };
    }

    private function recalculate(
        string $sheetIdentifier,
        CellReference $reference,
        SheetCellValueResolver $resolver,
        string $userIdentifier,
    ): ?CellModel {
        $cell = $this->cells->findAt($sheetIdentifier, $reference);
        if ($cell === null || $cell->cellKind() !== CellKind::Formula) {
            return null;
        }

        try {
            $value = $this->engine->evaluate((string) $cell->input, $resolver);
        } catch (SyntaxErrorException) {
            $value = CellValue::error(FormulaError::WrongValueType);
        }

        $resolver->remember($reference, $value);

        return $this->cells->saveComputedValue($sheetIdentifier, $reference, $value, $userIdentifier);
    }

    private function numberValue(string $number): CellValue
    {
        try {
            return CellValue::number(BigDecimal::of($number));
        } catch (MathException) {
            return CellValue::error(FormulaError::WrongValueType);
        }
    }
}
