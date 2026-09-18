<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class LoginRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }

    public function email(): string
    {
        return (string) $this->string('email');
    }

    public function password(): string
    {
        return (string) $this->string('password');
    }
}
