<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Requests;

use Finance\FormulaEngine\Exceptions\InvalidReferenceException;
use Finance\FormulaEngine\Values\CellRange;
use Finance\FormulaEngine\Values\CellReference;
use Illuminate\Foundation\Http\FormRequest;

final class FormatCellsRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'range.startRow' => ['required', 'integer', 'min:1'],
            'range.startColumn' => ['required', 'integer', 'min:1'],
            'range.endRow' => ['required', 'integer', 'min:1'],
            'range.endColumn' => ['required', 'integer', 'min:1'],
            'format' => ['required', 'array'],
            'format.background' => ['sometimes', 'nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'format.bold' => ['sometimes', 'nullable', 'boolean'],
            'format.italic' => ['sometimes', 'nullable', 'boolean'],
            'format.align' => ['sometimes', 'nullable', 'string', 'in:left,center,right'],
            'format.decimals' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:6'],
        ];
    }

    /**
     * @throws InvalidReferenceException
     */
    public function range(): CellRange
    {
        /** @var array{startRow: int, startColumn: int, endRow: int, endColumn: int} $range */
        $range = $this->array('range');

        return new CellRange(
            new CellReference((int) $range['startColumn'], (int) $range['startRow']),
            new CellReference((int) $range['endColumn'], (int) $range['endRow']),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function cellFormat(): array
    {
        return $this->array('format');
    }
}
