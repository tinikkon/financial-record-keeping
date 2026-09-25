<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ShiftColumnsRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'column' => ['required', 'integer', 'min:1'],
            'count' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function column(): int
    {
        return $this->integer('column');
    }

    /**
     * За раз обычно вставляют одну колонку, поэтому количество необязательно.
     */
    public function count(): int
    {
        return $this->has('count') ? $this->integer('count') : 1;
    }
}
