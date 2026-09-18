<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Evaluation;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Finance\FormulaEngine\Ast\BinaryOperationNode;
use Finance\FormulaEngine\Ast\FunctionCallNode;
use Finance\FormulaEngine\Ast\Node;
use Finance\FormulaEngine\Ast\NumberNode;
use Finance\FormulaEngine\Ast\RangeNode;
use Finance\FormulaEngine\Ast\ReferenceNode;
use Finance\FormulaEngine\Ast\TextNode;
use Finance\FormulaEngine\Ast\UnaryOperationNode;
use Finance\FormulaEngine\Contracts\CellValueResolver;
use Finance\FormulaEngine\Exceptions\EvaluationFailedException;
use Finance\FormulaEngine\Values\CellValue;
use Finance\FormulaEngine\Values\FormulaError;

/**
 * Вычисляет дерево разбора.
 *
 * Ошибка внутри выражения прерывает вычисление исключением и возвращается
 * наружу значением ячейки: ячейка, ссылающаяся на ошибочную, тоже ошибочна.
 */
final class Evaluator
{
    /**
     * Знаков после запятой, которые сохраняются при делении. Округление до копеек
     * делается на показе, а не при расчёте, иначе погрешность копится по цепочке.
     */
    private const int DIVISION_SCALE = 12;

    public function __construct(private readonly FunctionRegistry $functions)
    {
    }

    public function evaluate(Node $node, CellValueResolver $resolver): CellValue
    {
        try {
            return $this->evaluateNode($node, $resolver);
        } catch (EvaluationFailedException $exception) {
            return CellValue::error($exception->error());
        }
    }

    /**
     * @throws EvaluationFailedException
     */
    private function evaluateNode(Node $node, CellValueResolver $resolver): CellValue
    {
        return match (true) {
            $node instanceof NumberNode => CellValue::number($node->value),
            $node instanceof TextNode => CellValue::text($node->value),
            $node instanceof ReferenceNode => $resolver->valueAt($node->reference),
            $node instanceof RangeNode => throw new EvaluationFailedException(FormulaError::WrongValueType),
            $node instanceof UnaryOperationNode => $this->evaluateUnary($node, $resolver),
            $node instanceof BinaryOperationNode => $this->evaluateBinary($node, $resolver),
            $node instanceof FunctionCallNode => $this->evaluateFunctionCall($node, $resolver),
            default => throw new EvaluationFailedException(FormulaError::WrongValueType),
        };
    }

    /**
     * @throws EvaluationFailedException
     */
    private function evaluateUnary(UnaryOperationNode $node, CellValueResolver $resolver): CellValue
    {
        $operand = $this->evaluateNode($node->operand, $resolver)->toArithmeticNumber();

        return CellValue::number($node->operator === '-' ? $operand->negated() : $operand);
    }

    /**
     * @throws EvaluationFailedException
     */
    private function evaluateBinary(BinaryOperationNode $node, CellValueResolver $resolver): CellValue
    {
        $left = $this->evaluateNode($node->left, $resolver)->toArithmeticNumber();
        $right = $this->evaluateNode($node->right, $resolver)->toArithmeticNumber();

        return CellValue::number(match ($node->operator) {
            '+' => $left->plus($right),
            '-' => $left->minus($right),
            '*' => $left->multipliedBy($right),
            '/' => $this->divide($left, $right),
            '^' => $this->raiseToPower($left, $right),
            default => throw new EvaluationFailedException(FormulaError::WrongValueType),
        });
    }

    /**
     * @throws EvaluationFailedException
     */
    private function divide(BigDecimal $dividend, BigDecimal $divisor): BigDecimal
    {
        if ($divisor->isZero()) {
            throw new EvaluationFailedException(FormulaError::DivisionByZero);
        }

        try {
            return $dividend->dividedBy($divisor, self::DIVISION_SCALE, RoundingMode::HalfUp)
                ->strippedOfTrailingZeros();
        } catch (MathException $exception) {
            throw new EvaluationFailedException(FormulaError::WrongValueType, $exception);
        }
    }

    /**
     * Степень поддерживается только целая: дробные степени в таблице расходов
     * не встречаются, а точная арифметика их не считает.
     *
     * @throws EvaluationFailedException
     */
    private function raiseToPower(BigDecimal $base, BigDecimal $exponent): BigDecimal
    {
        try {
            $wholeExponent = $exponent->toInt();
        } catch (MathException $exception) {
            throw new EvaluationFailedException(FormulaError::WrongValueType, $exception);
        }

        if ($wholeExponent < 0) {
            return $this->divide(BigDecimal::one(), $base->power(-$wholeExponent));
        }

        return $base->power($wholeExponent);
    }

    /**
     * @throws EvaluationFailedException
     */
    private function evaluateFunctionCall(FunctionCallNode $node, CellValueResolver $resolver): CellValue
    {
        $function = $this->functions->find($node->name);

        if ($function === null) {
            throw new EvaluationFailedException(FormulaError::UnknownName);
        }

        $arguments = [];

        foreach ($node->arguments as $argument) {
            $arguments[] = $argument instanceof RangeNode
                ? $resolver->valuesIn($argument->range)
                : [$this->evaluateNode($argument, $resolver)];
        }

        return $function->evaluate(new FunctionArguments($arguments));
    }
}
