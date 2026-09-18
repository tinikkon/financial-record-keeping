import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { sheetApi } from '../shared/api/client';
import { dropRealtimeConnection, subscribeToSheet, whenConnectionStateChanges } from '../shared/api/realtime';
import type { Cell, CellEdit, Sheet, Workbook } from '../entities/sheet';

interface SheetContentResponse {
    sheet: Sheet;
    cells: Cell[];
}

interface AppliedEditsResponse {
    sheetVersion: number;
    cells: Cell[];
}

interface IncomingPacket {
    sheetVersion: number;
    cells: Cell[];
    actorId: string;
}

export const useSheetStore = defineStore('sheet', () => {
    const workbook = ref<Workbook | null>(null);
    const sheets = ref<Sheet[]>([]);
    const activeSheet = ref<Sheet | null>(null);
    const cells = ref<Map<string, Cell>>(new Map());
    const isConnected = ref(false);
    const errorMessage = ref<string | null>(null);
    const isLoading = ref(false);

    const version = computed(() => activeSheet.value?.version ?? 0);

    async function openWorkbook(): Promise<void> {
        isLoading.value = true;

        try {
            const response = await sheetApi.request<{ workbooks: Workbook[] }>('workbooks');
            const first = response.workbooks[0];

            workbook.value =
                first ??
                (await sheetApi.request<Workbook>('workbooks', {
                    method: 'POST',
                    body: { name: 'План финансов' },
                }));

            await loadSheets();
        } finally {
            isLoading.value = false;
        }
    }

    async function loadSheets(): Promise<void> {
        if (workbook.value === null) {
            return;
        }

        const response = await sheetApi.request<{ sheets: Sheet[] }>(`workbooks/${workbook.value.id}/sheets`);
        sheets.value = response.sheets;

        const current = response.sheets.find((sheet) => sheet.id === activeSheet.value?.id);
        const target = current ?? response.sheets[0];

        if (target === undefined) {
            await createSheet(currentMonthName());

            return;
        }

        await openSheet(target.id);
    }

    async function openSheet(sheetIdentifier: string): Promise<void> {
        const content = await sheetApi.request<SheetContentResponse>(`sheets/${sheetIdentifier}/cells`);

        activeSheet.value = content.sheet;
        cells.value = new Map(content.cells.map((cell) => [cell.address, cell]));

        listenToSheet(sheetIdentifier);
    }

    async function createSheet(name: string): Promise<void> {
        if (workbook.value === null) {
            return;
        }

        const created = await sheetApi.request<Sheet>(`workbooks/${workbook.value.id}/sheets`, {
            method: 'POST',
            body: { name },
        });

        sheets.value = [...sheets.value, created];
        await openSheet(created.id);
    }

    async function duplicateSheet(name: string): Promise<void> {
        if (activeSheet.value === null) {
            return;
        }

        const created = await sheetApi.request<Sheet>(`sheets/${activeSheet.value.id}/duplicate`, {
            method: 'POST',
            body: { name },
        });

        sheets.value = [...sheets.value, created];
        await openSheet(created.id);
    }

    async function applyEdits(edits: CellEdit[]): Promise<void> {
        if (activeSheet.value === null) {
            return;
        }

        errorMessage.value = null;

        try {
            const applied = await sheetApi.request<AppliedEditsResponse>(`sheets/${activeSheet.value.id}/cells`, {
                method: 'PATCH',
                body: { edits },
            });

            mergeCells(applied.cells, applied.sheetVersion);
        } catch (error) {
            errorMessage.value = error instanceof Error ? error.message : 'Правка не применилась';

            // Сервер отверг правку целиком, значит у него прежнее состояние —
            // перечитываем лист, чтобы экран ему соответствовал.
            await reloadActiveSheet();
        }
    }

    /**
     * Применяет пришедший по сокету пакет.
     *
     * Версия должна идти ровно следом за текущей. Разрыв означает, что между
     * ними было ещё что-то, до нас не доехавшее, — тогда лист перечитывается
     * целиком, а не достраивается по кускам.
     */
    async function applyIncomingPacket(packet: IncomingPacket): Promise<void> {
        if (activeSheet.value === null) {
            return;
        }

        if (packet.sheetVersion <= activeSheet.value.version) {
            return;
        }

        if (packet.sheetVersion !== activeSheet.value.version + 1) {
            await reloadActiveSheet();

            return;
        }

        mergeCells(packet.cells, packet.sheetVersion);
    }

    async function reloadActiveSheet(): Promise<void> {
        if (activeSheet.value === null) {
            return;
        }

        await openSheet(activeSheet.value.id);
    }

    function mergeCells(changed: Cell[], sheetVersion: number): void {
        const merged = new Map(cells.value);

        for (const cell of changed) {
            merged.set(cell.address, cell);
        }

        cells.value = merged;

        if (activeSheet.value !== null) {
            activeSheet.value = { ...activeSheet.value, version: sheetVersion };
        }
    }

    function listenToSheet(sheetIdentifier: string): void {
        whenConnectionStateChanges((connected) => {
            isConnected.value = connected;
        });

        subscribeToSheet<IncomingPacket>(sheetIdentifier, (packet) => void applyIncomingPacket(packet));
    }

    function reset(): void {
        dropRealtimeConnection();
        workbook.value = null;
        sheets.value = [];
        activeSheet.value = null;
        cells.value = new Map();
        isConnected.value = false;
    }

    return {
        workbook,
        sheets,
        activeSheet,
        cells,
        isConnected,
        isLoading,
        errorMessage,
        version,
        openWorkbook,
        openSheet,
        createSheet,
        duplicateSheet,
        applyEdits,
        applyIncomingPacket,
        reloadActiveSheet,
        reset,
    };
});

/**
 * Название листа для текущего месяца в том же виде, что и в исходной таблице.
 */
export function currentMonthName(): string {
    const now = new Date();
    const month = `${now.getMonth() + 1}`.padStart(2, '0');
    const year = `${now.getFullYear()}`.slice(-2);

    return `${month}.${year}`;
}
