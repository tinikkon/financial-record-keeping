<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Evaluation\Functions;

use Brick\Math\BigDecimal;
use Finance\FormulaEngine\Evaluation\FormulaFunction;
use Finance\FormulaEngine\Evaluation\FunctionArguments;
use Finance\FormulaEngine\Exceptions\EvaluationFailedException;
use Finance\FormulaEngine\Values\CellValue;

/**
 * Сумма значений. Пустые ячейки и текст внутри диапазона пропускаются —
 * так же ведёт себя Excel, и это важно: в колонке расходов вперемешку лежат
 * числа и подписи, и подпись не должна ломать итог.
 */
final class SumFunction implements FormulaFunction
{
    public function names(): array
    {
        return ['СУММ', 'SUM'];
    }

    /**
     * @throws EvaluationFailedException
     */
    public function evaluate(FunctionArguments $arguments): CellValue
    {
        $total = BigDecimal::zero();

        foreach ($arguments->flattened() as $value) {
            $error = $value->errorValue();

            if ($error !== null) {
                throw new EvaluationFailedException($error);
            }

            $number = $value->numberValue();

            if ($number === null) {
                continue;
            }

            $total = $total->plus($number);
        }

        return CellValue::number($total);
    }
}
