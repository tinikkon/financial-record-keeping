import { describe, expect, test } from 'vitest';
import { cellAddress, columnToLetters, displayedText, type Cell } from '../sheet';

function ячейка(overrides: Partial<Cell>): Cell {
    return {
        address: 'B3',
        row: 3,
        column: 2,
        input: null,
        kind: 'number',
        value: null,
        error: null,
        format: {},
        ...overrides,
    };
}

describe('адреса ячеек', () => {
    test.each([
        [1, 'A'],
        [26, 'Z'],
        [27, 'AA'],
        [52, 'AZ'],
        [53, 'BA'],
        [702, 'ZZ'],
    ])('колонка %i записывается как %s', (column, letters) => {
        expect(columnToLetters(column)).toBe(letters);
    });

    test('адрес собирается из колонки и строки', () => {
        expect(cellAddress(91, 2)).toBe('B91');
    });
});

describe('показ содержимого ячейки', () => {
    test('пустая ячейка ничего не показывает', () => {
        expect(displayedText(undefined)).toBe('');
        expect(displayedText(ячейка({ value: null }))).toBe('');
    });

    test('ошибка показывается вместо значения', () => {
        expect(displayedText(ячейка({ value: '100', error: '#ДЕЛ/0!' }))).toBe('#ДЕЛ/0!');
    });

    test('число округляется до заданного числа знаков', () => {
        expect(displayedText(ячейка({ value: '121.993725', format: { decimals: 2 } }))).toBe('121.99');
    });

    test('без указания знаков число показывается как есть', () => {
        expect(displayedText(ячейка({ value: '121.993725' }))).toBe('121.993725');
    });

    test('текст не округляется, даже если задано число знаков', () => {
        expect(displayedText(ячейка({ kind: 'text', value: 'Продукты', format: { decimals: 2 } }))).toBe('Продукты');
    });
});
