<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Requests;

use Finance\Domains\Cells\Actions\CellEdit;
use Finance\FormulaEngine\Exceptions\InvalidReferenceException;
use Finance\FormulaEngine\Values\CellReference;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateCellsRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'edits' => ['required', 'array', 'min:1', 'max:500'],
            'edits.*.row' => ['required', 'integer', 'min:1'],
            'edits.*.column' => ['required', 'integer', 'min:1'],
            'edits.*.input' => ['present', 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return list<CellEdit>
     *
     * @throws InvalidReferenceException
     */
    public function edits(): array
    {
        $edits = [];

        /** @var array{row: int, column: int, input: string|null} $edit */
        foreach ($this->array('edits') as $edit) {
            $edits[] = new CellEdit(
                new CellReference((int) $edit['column'], (int) $edit['row']),
                $edit['input'] === null ? null : (string) $edit['input'],
            );
        }

        return $edits;
    }
}
