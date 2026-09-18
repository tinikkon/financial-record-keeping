<?php

declare(strict_types=1);

namespace Finance\Domains\History\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RestoreSheetRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
        ];
    }

    public function version(): int
    {
        return $this->integer('version');
    }
}
