<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Evaluation;

use Finance\FormulaEngine\Evaluation\Functions\SumFunction;

/**
 * Справочник функций формул. Имена приводятся к верхнему регистру с учётом
 * кириллицы, поэтому «сумм», «СУММ» и «Sum» ведут к одной и той же функции.
 */
final class FunctionRegistry
{
    /** @var array<string, FormulaFunction> */
    private array $functions = [];

    public function __construct()
    {
        $this->register(new SumFunction());
    }

    public function register(FormulaFunction $function): void
    {
        foreach ($function->names() as $name) {
            $this->functions[$this->normalise($name)] = $function;
        }
    }

    public function find(string $name): ?FormulaFunction
    {
        return $this->functions[$this->normalise($name)] ?? null;
    }

    private function normalise(string $name): string
    {
        return mb_strtoupper($name, 'UTF-8');
    }
}
