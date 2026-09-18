export interface Workbook {
    id: string;
    name: string;
    ownerId: string;
    memberIds: string[];
}

export interface Sheet {
    id: string;
    workbookId: string;
    name: string;
    position: number;
    version: number;
    rowCount: number;
    columnCount: number;
    columnWidths: Record<string, number>;
}

export interface CellFormat {
    background?: string;
    bold?: boolean;
    italic?: boolean;
    align?: 'left' | 'center' | 'right';
    decimals?: number;
}

export interface Cell {
    address: string;
    row: number;
    column: number;
    input: string | null;
    kind: 'number' | 'text' | 'formula' | 'empty';
    value: string | null;
    error: string | null;
    format: CellFormat;
}

export interface CellEdit {
    row: number;
    column: number;
    input: string | null;
}

/**
 * Номер колонки в буквенный адрес: 1 — это A, 27 — AA.
 */
export function columnToLetters(column: number): string {
    let letters = '';
    let remaining = column;

    while (remaining > 0) {
        const remainder = (remaining - 1) % 26;
        letters = String.fromCharCode(65 + remainder) + letters;
        remaining = Math.floor((remaining - 1 - remainder) / 26);
    }

    return letters;
}

export function cellAddress(row: number, column: number): string {
    return `${columnToLetters(column)}${row}`;
}

/**
 * Что показывать в ячейке: ошибку, вычисленное значение или пустоту.
 * Сырая формула в ячейке не показывается — она видна только в строке формул.
 */
export function displayedText(cell: Cell | undefined): string {
    if (cell === undefined) {
        return '';
    }

    if (cell.error !== null) {
        return cell.error;
    }

    if (cell.value === null) {
        return '';
    }

    if (cell.kind === 'text' || cell.format.decimals === undefined) {
        return cell.value;
    }

    const asNumber = Number(cell.value);

    return Number.isNaN(asNumber) ? cell.value : asNumber.toFixed(cell.format.decimals);
}
