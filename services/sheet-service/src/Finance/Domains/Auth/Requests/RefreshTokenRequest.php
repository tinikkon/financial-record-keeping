<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RefreshTokenRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'refreshToken' => ['required', 'string', 'size:96'],
        ];
    }

    public function refreshToken(): string
    {
        return (string) $this->string('refreshToken');
    }
}
