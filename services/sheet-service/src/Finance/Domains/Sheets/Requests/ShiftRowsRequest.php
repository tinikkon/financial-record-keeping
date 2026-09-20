<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ShiftRowsRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'row' => ['required', 'integer', 'min:1'],
            'count' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function row(): int
    {
        return $this->integer('row');
    }

    /**
     * За раз обычно вставляют одну строку, поэтому количество необязательно.
     */
    public function count(): int
    {
        return $this->has('count') ? $this->integer('count') : 1;
    }
}
