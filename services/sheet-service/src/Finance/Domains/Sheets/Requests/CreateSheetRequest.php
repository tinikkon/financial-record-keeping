<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateSheetRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:60'],
        ];
    }

    public function name(): string
    {
        return (string) $this->string('name');
    }
}
