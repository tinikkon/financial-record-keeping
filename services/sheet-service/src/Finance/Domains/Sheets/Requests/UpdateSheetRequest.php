<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Requests;

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

    /**
     * @return array<string, int>|null
     */
    public function columnWidths(): ?array
    {
        if (! $this->has('columnWidths')) {
            return null;
        }

        /** @var array<string, int> $widths */
        $widths = array_map(intval(...), $this->array('columnWidths'));

        return $widths;
    }
}
