import { beforeEach, describe, expect, test, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import type { Cell, Sheet } from '../../entities/sheet';

const запросы = vi.hoisted(() => ({ выполнить: vi.fn() }));

vi.mock('../../shared/api/client', () => ({
    sheetApi: { request: запросы.выполнить },
    historyApi: { request: vi.fn() },
}));

vi.mock('../../shared/api/realtime', () => ({
    whenConnectionStateChanges: vi.fn(),
    subscribeToSheet: vi.fn(),
    dropRealtimeConnection: vi.fn(),
}));

const { useSheetStore } = await import('../sheet');

function лист(version: number): Sheet {
    return {
        id: 'лист-1',
        workbookId: 'книга-1',
        name: '10.26',
        position: 0,
        version,
        rowCount: 200,
        columnCount: 26,
        columnWidths: {},
    };
}

function ячейка(address: string, value: string): Cell {
    return {
        address,
        row: 3,
        column: 2,
        input: value,
        kind: 'number',
        value,
        error: null,
        format: {},
    };
}

describe('приём пакетов изменений', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        запросы.выполнить.mockReset();
    });

    test('пакет следующей версии применяется к ячейкам', async () => {
        const store = useSheetStore();
        store.activeSheet = лист(4);

        await store.applyIncomingPacket({ sheetVersion: 5, cells: [ячейка('B3', '120')], actorId: 'кто-то' });

        expect(store.cells.get('B3')?.value).toBe('120');
        expect(store.activeSheet?.version).toBe(5);
        expect(запросы.выполнить).not.toHaveBeenCalled();
    });

    test('устаревший пакет пропускается', async () => {
        const store = useSheetStore();
        store.activeSheet = лист(7);

        await store.applyIncomingPacket({ sheetVersion: 6, cells: [ячейка('B3', '999')], actorId: 'кто-то' });

        expect(store.cells.size).toBe(0);
        expect(store.activeSheet?.version).toBe(7);
    });

    test('разрыв в версиях вызывает полную перезагрузку листа', async () => {
        запросы.выполнить.mockResolvedValue({ sheet: лист(9), cells: [ячейка('B3', '300')] });

        const store = useSheetStore();
        store.activeSheet = лист(4);

        await store.applyIncomingPacket({ sheetVersion: 9, cells: [ячейка('B3', '300')], actorId: 'кто-то' });

        expect(запросы.выполнить).toHaveBeenCalledWith('sheets/лист-1/cells');
        expect(store.activeSheet?.version).toBe(9);
    });

    test('пакет без открытого листа ничего не делает', async () => {
        const store = useSheetStore();

        await store.applyIncomingPacket({ sheetVersion: 1, cells: [ячейка('B3', '120')], actorId: 'кто-то' });

        expect(store.cells.size).toBe(0);
    });
});
