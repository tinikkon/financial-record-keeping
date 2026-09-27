<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Actions;

use Finance\Domains\Cells\Contracts\CellRepositoryContract;
use Finance\Domains\Cells\Models\CellModel;
use Finance\Domains\Sheets\Models\SheetModel;

/**
 * Лист в CSV.
 *
 * Выгружается то, что видно на экране: посчитанные значения, а не формулы —
 * файл открывают, чтобы посмотреть и переслать, а не чтобы пересчитывать.
 *
 * Разделитель — точка с запятой, дробная часть — через запятую: так файл
 * открывается двойным щелчком в Excel с русскими настройками. Впереди метка
 * порядка байтов, иначе Excel показывает кириллицу набором вопросительных знаков.
 */
final readonly class ExportSheetToCsvAction
{
    private const BYTE_ORDER_MARK = "\u{FEFF}";
    private const SEPARATOR = ';';

    public function __construct(private CellRepositoryContract $cells)
    {
    }

    public function execute(SheetModel $sheet): string
    {
        $texts = [];
        $lastRow = 0;
        $lastColumn = 0;

        foreach ($this->cells->forSheet($sheet->_id) as $cell) {
            $text = $this->cellText($cell);

            if ($text === '') {
                continue;
            }

            $texts[$cell->row][$cell->column] = $text;
            $lastRow = max($lastRow, $cell->row);
            $lastColumn = max($lastColumn, $cell->column);
        }

        $lines = [];

        for ($row = 1; $row <= $lastRow; $row++) {
            $fields = [];

            for ($column = 1; $column <= $lastColumn; $column++) {
                $fields[] = $this->escaped($texts[$row][$column] ?? '');
            }

            $lines[] = implode(self::SEPARATOR, $fields);
        }

        // Конец строки — возврат каретки с переводом: Excel понимает оба вида,
        // а часть программ под Windows — только этот.
        return self::BYTE_ORDER_MARK . implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Ячейка в том же виде, в каком её показывает таблица.
     */
    private function cellText(CellModel $cell): string
    {
        if ($cell->error !== null) {
            return $cell->error;
        }

        if ($cell->value_number === null) {
            return $cell->value_text ?? '';
        }

        $number = (string) $cell->value_number->strippedOfTrailingZeros();
        $decimals = $cell->cellFormat()->decimals;

        if ($decimals !== null) {
            $number = number_format((float) $number, $decimals, '.', '');
        }

        return str_replace('.', ',', $number);
    }

    private function escaped(string $value): string
    {
        $needsQuotes = str_contains($value, self::SEPARATOR)
            || str_contains($value, '"')
            || str_contains($value, "\n")
            || str_contains($value, "\r");

        if (!$needsQuotes) {
            return $value;
        }

        return '"' . str_replace('"', '""', $value) . '"';
    }
}
