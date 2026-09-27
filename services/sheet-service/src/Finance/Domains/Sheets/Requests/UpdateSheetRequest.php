<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Requests;

use Finance\Domains\Sheets\Data\ColumnWidths;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSheetRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:1', 'max:60'],
            'columnWidths' => ['sometimes', 'array'],
            'columnWidths.*' => ['integer', 'min:40', 'max:600'],
        ];
    }

    public function newName(): ?string
    {
        return $this->has('name') ? (string) $this->string('name') : null;
    }

    public function columnWidths(): ?ColumnWidths
    {
        if (! $this->has('columnWidths')) {
            return null;
        }

        return ColumnWidths::fromArray($this->array('columnWidths'));
    }
}
