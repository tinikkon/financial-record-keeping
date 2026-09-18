export interface ColumnTotal {
    column: string;
    total: string;
    filledCells: number;
}

export interface SheetSummary {
    sheetId: string;
    sheetName: string;
    changes: number;
    lastChangeAt: string | null;
    columns: ColumnTotal[];
}
