<?php

declare(strict_types=1);

namespace Finance\Domains\Workbooks\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateWorkbookRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:120'],
        ];
    }

    public function name(): string
    {
        return (string) $this->string('name');
    }
}
