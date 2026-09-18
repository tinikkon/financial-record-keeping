<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReorderSheetsRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'sheetIds' => ['required', 'array', 'min:1'],
            'sheetIds.*' => ['required', 'string'],
        ];
    }

    /**
     * @return list<string>
     */
    public function sheetIdentifiers(): array
    {
        /** @var list<string> $identifiers */
        $identifiers = array_values(array_map(strval(...), $this->array('sheetIds')));

        return $identifiers;
    }
}
