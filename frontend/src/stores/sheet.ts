import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { sheetApi } from '../shared/api/client';
import { dropRealtimeConnection, subscribeToSheet, whenConnectionStateChanges } from '../shared/api/realtime';
import { saveFile } from '../shared/lib/files';
import type { Cell, CellEdit, CellFormat, Sheet, Workbook } from '../entities/sheet';

interface SheetContentResponse {
    sheet: Sheet;
    cells: Cell[];
}

interface AppliedEditsResponse {
    sheetVersion: number;
    cells: Cell[];
}

/**
 * Ответ на перестройку листа: к пачке правок добавлены ширины колонок,
 * которые переезжают при сдвиге колонок.
 */
interface ShiftedSheetResponse extends AppliedEditsResponse {
    columnWidths: Record<string, number>;
}

export interface CellRangeSelection {
    startRow: number;
    startColumn: number;
    endRow: number;
    endColumn: number;
}

interface IncomingPacket {
    sheetVersion: number;
    cells: Cell[];
    actorId: string;
}

const ХРАНИЛИЩЕ_ОТКРЫТОГО_ЛИСТА = 'finance.activeSheetId';

function rememberedSheetIdentifier(): string | null {
    try {
        return localStorage.getItem(ХРАНИЛИЩЕ_ОТКРЫТОГО_ЛИСТА);
    } catch {
        return null;
    }
}

function rememberSheet(sheetIdentifier: string): void {
    try {
        localStorage.setItem(ХРАНИЛИЩЕ_ОТКРЫТОГО_ЛИСТА, sheetIdentifier);
    } catch {
        // Приватный режим браузера может запрещать запись — приложение просто
        // откроет первый месяц.
    }
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

        // После перезагрузки открывается тот же месяц, что был открыт: возвращать
        // человека каждый раз в первый месяц — значит заставлять его искать своё место.
        const запомненный = response.sheets.find((sheet) => sheet.id === rememberedSheetIdentifier());
        const current = response.sheets.find((sheet) => sheet.id === activeSheet.value?.id);
        const target = current ?? запомненный ?? response.sheets[0];

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
        rememberSheet(sheetIdentifier);

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

    /**
     * Переименовывает месяц.
     */
    async function renameSheet(sheetIdentifier: string, name: string): Promise<void> {
        try {
            const updated = await sheetApi.request<Sheet>(`sheets/${sheetIdentifier}`, {
                method: 'PATCH',
                body: { name },
            });

            replaceSheet(updated);
        } catch (error) {
            errorMessage.value = error instanceof Error ? error.message : 'Не удалось переименовать месяц';
        }
    }

    /**
     * Удаляет месяц вместе с его ячейками и открывает соседний.
     */
    async function deleteSheet(sheetIdentifier: string): Promise<void> {
        try {
            await sheetApi.request(`sheets/${sheetIdentifier}`, { method: 'DELETE' });
        } catch (error) {
            errorMessage.value = error instanceof Error ? error.message : 'Не удалось удалить месяц';

            return;
        }

        sheets.value = sheets.value.filter((sheet) => sheet.id !== sheetIdentifier);

        if (activeSheet.value?.id !== sheetIdentifier) {
            return;
        }

        const next = sheets.value[0];

        if (next === undefined) {
            activeSheet.value = null;
            cells.value = new Map();

            return;
        }

        await openSheet(next.id);
    }

    /**
     * Меняет ширину одной колонки, не трогая остальные.
     */
    async function updateColumnWidth(column: string, width: number): Promise<void> {
        if (activeSheet.value === null) {
            return;
        }

        const widths = { ...activeSheet.value.columnWidths, [column]: width };
        activeSheet.value = { ...activeSheet.value, columnWidths: widths };

        try {
            const updated = await sheetApi.request<Sheet>(`sheets/${activeSheet.value.id}`, {
                method: 'PATCH',
                body: { columnWidths: widths },
            });

            replaceSheet(updated);
        } catch (error) {
            errorMessage.value = error instanceof Error ? error.message : 'Не удалось изменить ширину';

            await reloadActiveSheet();
        }
    }

    function replaceSheet(updated: Sheet): void {
        sheets.value = sheets.value.map((sheet) => (sheet.id === updated.id ? updated : sheet));

        if (activeSheet.value?.id === updated.id) {
            // Версия листа приходит из описания и может отставать от применённых
            // правок, поэтому берётся большая из двух.
            activeSheet.value = { ...updated, version: Math.max(updated.version, activeSheet.value.version) };
        }
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
     * Меняет оформление одной ячейки.
     *
     * Свойство со значением undefined сервер понимает как снятие: так можно
     * убрать заливку, не сбрасывая заодно жирность.
     */
    async function applyFormat(range: CellRangeSelection, format: CellFormat): Promise<void> {
        if (activeSheet.value === null) {
            return;
        }

        const свойства: Record<string, unknown> = {};

        for (const [имя, значение] of Object.entries(format)) {
            свойства[имя] = значение === undefined ? null : значение;
        }

        try {
            const applied = await sheetApi.request<AppliedEditsResponse>(
                `sheets/${activeSheet.value.id}/cells/format`,
                {
                    method: 'PATCH',
                    body: { range, format: свойства },
                },
            );

            mergeCells(applied.cells, applied.sheetVersion);
        } catch (error) {
            errorMessage.value = error instanceof Error ? error.message : 'Оформление не применилось';
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

    /**
     * Возвращает лист к состоянию на указанную версию.
     *
     * Прошлое при этом не переписывается: возврат сам становится новой версией,
     * и отменить его можно тем же способом.
     */
    async function restoreToVersion(version: number): Promise<void> {
        if (activeSheet.value === null) {
            return;
        }

        errorMessage.value = null;

        try {
            const applied = await sheetApi.request<AppliedEditsResponse>(
                `sheets/${activeSheet.value.id}/restore`,
                { method: 'POST', body: { version } },
            );

            mergeCells(applied.cells, applied.sheetVersion);
            await reloadActiveSheet();
        } catch (error) {
            errorMessage.value = error instanceof Error ? error.message : 'Не удалось вернуть лист';
        }
    }

    /**
     * Стирает содержимое выделенного прямоугольника одной пачкой правок.
     */
    async function clearRange(range: CellRangeSelection): Promise<void> {
        const edits: CellEdit[] = [];

        for (let row = range.startRow; row <= range.endRow; row++) {
            for (let column = range.startColumn; column <= range.endColumn; column++) {
                edits.push({ row, column, input: null });
            }
        }

        await applyEdits(edits);
    }

    /**
     * Вставляет строку перед указанной: всё, что ниже, съезжает вниз вместе
     * со ссылками формул.
     */
    async function insertRow(row: number): Promise<void> {
        await shiftLines('rows', 'insert', { row });
    }

    /**
     * Удаляет строку: нижние поднимаются на её место.
     */
    async function deleteRow(row: number): Promise<void> {
        await shiftLines('rows', 'delete', { row });
    }

    /**
     * Вставляет колонку перед указанной: всё, что правее, съезжает вправо
     * вместе со ссылками формул и ширинами.
     */
    async function insertColumn(column: number): Promise<void> {
        await shiftLines('columns', 'insert', { column });
    }

    /**
     * Удаляет колонку: правые сдвигаются на её место.
     */
    async function deleteColumn(column: number): Promise<void> {
        await shiftLines('columns', 'delete', { column });
    }

    async function shiftLines(
        axis: 'rows' | 'columns',
        operation: 'insert' | 'delete',
        body: { row: number } | { column: number },
    ): Promise<void> {
        if (activeSheet.value === null) {
            return;
        }

        errorMessage.value = null;

        try {
            const applied = await sheetApi.request<ShiftedSheetResponse>(
                `sheets/${activeSheet.value.id}/${axis}/${operation}`,
                { method: 'POST', body },
            );

            replaceSheet({ ...activeSheet.value, columnWidths: applied.columnWidths });
            mergeCells(applied.cells, applied.sheetVersion);
        } catch (error) {
            errorMessage.value = error instanceof Error ? error.message : 'Не удалось перестроить лист';

            // Перестройка либо прошла целиком, либо не начиналась, но проверять
            // это на глаз не надо: экран приводится к тому, что на сервере.
            await reloadActiveSheet();
        }
    }

    /**
     * Отдаёт открытый лист файлом CSV: посчитанными значениями, как на экране.
     */
    async function exportActiveSheetToCsv(): Promise<void> {
        if (activeSheet.value === null) {
            return;
        }

        errorMessage.value = null;

        try {
            const file = await sheetApi.requestFile(`sheets/${activeSheet.value.id}/csv`);

            saveFile(file, `${activeSheet.value.name}.csv`);
        } catch (error) {
            errorMessage.value = error instanceof Error ? error.message : 'Не удалось выгрузить лист';
        }
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
        applyFormat,
        clearRange,
        applyIncomingPacket,
        exportActiveSheetToCsv,
        insertRow,
        deleteRow,
        insertColumn,
        deleteColumn,
        renameSheet,
        deleteSheet,
        updateColumnWidth,
        restoreToVersion,
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
