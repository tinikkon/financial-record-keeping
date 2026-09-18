<?php

declare(strict_types=1);

namespace Finance\Domains\Core\Casts;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use MongoDB\BSON\Decimal128;

/**
 * Хранит денежные значения в типе Decimal128.
 *
 * MongoDB умеет хранить десятичные числа точно, отдельным типом. Обычное число
 * в базе — двоичное с плавающей точкой, и сумма расходов на нём начинает
 * расходиться в копейках. Строкой хранить тоже нельзя: по строке не построить
 * ни сортировку, ни суммирование в конвейере агрегации.
 *
 * @implements CastsAttributes<BigDecimal|null, BigDecimal|string|null>
 */
final class Decimal128Cast implements CastsAttributes
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?BigDecimal
    {
        if ($value === null) {
            return null;
        }

        try {
            return BigDecimal::of((string) $value);
        } catch (MathException) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?Decimal128
    {
        if ($value === null) {
            return null;
        }

        return new Decimal128((string) $value);
    }
}
