export interface CellChange {
    id: string;
    sheetId: string;
    sheetName: string;
    address: string;
    valueBefore: string | null;
    valueAfter: string | null;
    inputBefore: string | null;
    inputAfter: string | null;
    sheetVersion: number;
    actorId: string;
    occurredAt: string;
}

/**
 * Человеческое описание правки: «было 120, стало 300».
 */
export function describeChange(change: CellChange): string {
    const было = change.valueBefore ?? 'пусто';
    const стало = change.valueAfter ?? 'пусто';

    return `${было} → ${стало}`;
}

export function formatMoment(iso: string): string {
    const дата = new Date(iso);

    return дата.toLocaleString('ru', {
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}
