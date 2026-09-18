<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Support;

use Finance\Domains\Auth\Exceptions\InvalidAccessTokenException;
use Finance\Domains\Auth\Models\UserModel;
use Illuminate\Http\Request;

/**
 * Достаёт пользователя, положенного в запрос посредником проверки токена.
 *
 * Отдельный класс, потому что иначе проверка типа повторялась бы в каждом
 * контроллере, а посредник уже гарантировал, что пользователь есть.
 */
final readonly class CurrentUser
{
    /**
     * @throws InvalidAccessTokenException
     */
    public static function of(Request $request): UserModel
    {
        $user = $request->user();

        if (! $user instanceof UserModel) {
            throw new InvalidAccessTokenException();
        }

        return $user;
    }

    /**
     * @throws InvalidAccessTokenException
     */
    public static function identifier(Request $request): string
    {
        return self::of($request)->identifier();
    }
}
