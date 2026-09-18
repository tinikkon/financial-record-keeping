<?php

declare(strict_types=1);

namespace Finance\FormulaEngine\Values;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Finance\FormulaEngine\Exceptions\EvaluationFailedException;

/**
 * Значение ячейки: число, текст, пустота или ошибка.
 *
 * Числа хранятся точной десятичной арифметикой. Деньги нельзя считать числами
 * с плавающей точкой: 0.1 + 0.2 там не равно 0.3, и в таблице расходов это
 * рано или поздно вылезает расхождением в копейку.
 */
final readonly class CellValue
{
    private function __construct(
        private ?BigDecimal $number,
        private ?string $text,
        private ?FormulaError $error,
        private bool $blank,
    ) {
    }

    public static function number(BigDecimal $number): self
    {
        return new self($number, null, null, false);
    }

    /**
     * @throws EvaluationFailedException если строка не является числом
     */
    public static function numberFromString(string $number): self
    {
        try {
            return self::number(BigDecimal::of($number));
        } catch (MathException $exception) {
            throw new EvaluationFailedException(FormulaError::WrongValueType, $exception);
        }
    }

    public static function text(string $text): self
    {
        return new self(null, $text, null, false);
    }

    public static function blank(): self
    {
        return new self(null, null, null, true);
    }

    public static function error(FormulaError $error): self
    {
        return new self(null, null, $error, false);
    }

    public function isNumber(): bool
    {
        return $this->number !== null;
    }

    public function isText(): bool
    {
        return $this->text !== null;
    }

    public function isBlank(): bool
    {
        return $this->blank;
    }

    public function isError(): bool
    {
        return $this->error !== null;
    }

    public function errorValue(): ?FormulaError
    {
        return $this->error;
    }

    public function textValue(): ?string
    {
        return $this->text;
    }

    public function numberValue(): ?BigDecimal
    {
        return $this->number;
    }

    /**
     * Привести значение к числу по правилам таблицы: пустая ячейка считается нулём,
     * текст из одних цифр — числом, остальной текст даёт ошибку типа.
     *
     * @throws EvaluationFailedException
     */
    public function toArithmeticNumber(): BigDecimal
    {
        if ($this->error !== null) {
            throw new EvaluationFailedException($this->error);
        }

        if ($this->number !== null) {
            return $this->number;
        }

        if ($this->blank) {
            return BigDecimal::zero();
        }

        try {
            return BigDecimal::of((string) $this->text);
        } catch (MathException $exception) {
            throw new EvaluationFailedException(FormulaError::WrongValueType, $exception);
        }
    }

    /**
     * Представление для показа в интерфейсе и для хранения.
     */
    public function toDisplayString(): string
    {
        if ($this->error !== null) {
            return $this->error->value;
        }

        if ($this->number !== null) {
            return (string) $this->number->strippedOfTrailingZeros();
        }

        if ($this->blank) {
            return '';
        }

        return (string) $this->text;
    }
}
