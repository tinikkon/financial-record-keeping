<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Support;

use Finance\Domains\Auth\Exceptions\InvalidAccessTokenException;
use Finance\Domains\Auth\Middleware\AuthenticateWithAccessToken;
use Illuminate\Http\Request;

final readonly class CurrentUser
{
    /**
     * @throws InvalidAccessTokenException
     */
    public static function identifier(Request $request): string
    {
        $identifier = $request->attributes->get(AuthenticateWithAccessToken::USER_ATTRIBUTE);

        if (! is_string($identifier) || $identifier === '') {
            throw new InvalidAccessTokenException();
        }

        return $identifier;
    }
}
