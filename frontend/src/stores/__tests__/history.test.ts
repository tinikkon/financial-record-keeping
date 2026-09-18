import { beforeEach, describe, expect, test, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import type { CellChange } from '../../entities/history';
import { describeChange } from '../../entities/history';

const запросы = vi.hoisted(() => ({ выполнить: vi.fn() }));

vi.mock('../../shared/api/client', () => ({
    sheetApi: { request: vi.fn() },
    historyApi: { request: запросы.выполнить },
}));

const { useHistoryStore } = await import('../history');

function правка(overrides: Partial<CellChange> = {}): CellChange {
    return {
        id: '1',
        sheetId: 'лист-1',
        sheetName: '10.26',
        address: 'B3',
        valueBefore: '120',
        valueAfter: '300',
        inputBefore: '120',
        inputAfter: '300',
        sheetVersion: 2,
        actorId: 'кто-то',
        occurredAt: '2026-09-18T10:00:00+00:00',
        ...overrides,
    };
}

describe('описание правки', () => {
    test('показывает, что было и что стало', () => {
        expect(describeChange(правка())).toBe('120 → 300');
    });

    test('появление значения в пустой ячейке читается понятно', () => {
        expect(describeChange(правка({ valueBefore: null }))).toBe('пусто → 300');
    });

    test('очистка ячейки читается понятно', () => {
        expect(describeChange(правка({ valueAfter: null }))).toBe('120 → пусто');
    });
});

describe('панель истории', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        запросы.выполнить.mockReset();
    });

    test('история запрашивается по адресу ячейки и открывает панель', async () => {
        запросы.выполнить.mockResolvedValue({ changes: [правка()] });

        const store = useHistoryStore();
        await store.showForCell('лист-1', 'B3');

        expect(запросы.выполнить).toHaveBeenCalledWith('sheets/лист-1/cells/B3/history');
        expect(store.isOpen).toBe(true);
        expect(store.changes).toHaveLength(1);
        expect(store.address).toBe('B3');
    });

    test('недоступная история не роняет панель, а показывает сообщение', async () => {
        запросы.выполнить.mockRejectedValue(new Error('История сейчас недоступна'));

        const store = useHistoryStore();
        await store.showForCell('лист-1', 'B3');

        expect(store.isOpen).toBe(true);
        expect(store.changes).toHaveLength(0);
        expect(store.errorMessage).toBe('История сейчас недоступна');
    });

    test('закрытие очищает показанное', async () => {
        запросы.выполнить.mockResolvedValue({ changes: [правка()] });

        const store = useHistoryStore();
        await store.showForCell('лист-1', 'B3');
        store.close();

        expect(store.isOpen).toBe(false);
        expect(store.changes).toHaveLength(0);
        expect(store.address).toBeNull();
    });
});
