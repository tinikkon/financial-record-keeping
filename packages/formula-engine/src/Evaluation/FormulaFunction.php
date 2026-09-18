<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Evaluation;

use Finance\FormulaEngine\Exceptions\EvaluationFailedException;
use Finance\FormulaEngine\Values\CellValue;

interface FormulaFunction
{
    /**
     * Имена, по которым функция вызывается в формуле. Регистр не важен,
     * русское и английское написание равноправны.
     *
     * @return list<string>
     */
    public function names(): array;

    /**
     * @throws EvaluationFailedException
     */
    public function evaluate(FunctionArguments $arguments): CellValue;
}
