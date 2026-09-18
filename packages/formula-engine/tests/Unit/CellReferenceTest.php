<?php

declare(strict_types=1);

use Finance\FormulaEngine\Exceptions\InvalidReferenceException;
use Finance\FormulaEngine\Values\CellRange;
use Finance\FormulaEngine\Values\CellReference;

test('ссылка разбирается из строки', function (): void {
    $reference = CellReference::fromString('B3');

    expect($reference->column)->toBe(2)
        ->and($reference->row)->toBe(3)
        ->and($reference->columnFixed)->toBeFalse()
        ->and($reference->rowFixed)->toBeFalse();
});

test('закрепление координат читается из знаков доллара', function (): void {
    $reference = CellReference::fromString('$F$2');

    expect($reference->columnFixed)->toBeTrue()
        ->and($reference->rowFixed)->toBeTrue()
        ->and($reference->toString())->toBe('$F$2')
        ->and($reference->key())->toBe('F2');
});

test('буквы колонок переводятся в номера и обратно', function (string $letters, int $column): void {
    expect(CellReference::lettersToColumn($letters))->toBe($column)
        ->and(CellReference::columnToLetters($column))->toBe($letters);
})->with([
    ['A', 1],
    ['Z', 26],
    ['AA', 27],
    ['AZ', 52],
    ['BA', 53],
    ['ZZ', 702],
]);

test('при копировании сдвигаются только незакреплённые координаты', function (): void {
    $shifted = CellReference::fromString('C$2')->shifted(rowDelta: 5, columnDelta: 1);

    expect($shifted->toString())->toBe('D$2');
});

test('ссылка за границами листа отвергается', function (): void {
    new CellReference(column: 0, row: 1);
})->throws(InvalidReferenceException::class);

test('границы диапазона приводятся к нормальному виду', function (): void {
    $range = CellRange::fromString('B90:B3');

    expect($range->minimumRow)->toBe(3)
        ->and($range->maximumRow)->toBe(90)
        ->and($range->cellCount())->toBe(88)
        ->and($range->contains(CellReference::fromString('B50')))->toBeTrue()
        ->and($range->contains(CellReference::fromString('C50')))->toBeFalse();
});
