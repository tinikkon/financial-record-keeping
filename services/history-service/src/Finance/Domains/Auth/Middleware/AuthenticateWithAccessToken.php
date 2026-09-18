<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Middleware;

use Closure;
use Finance\Domains\Auth\Exceptions\InvalidAccessTokenException;
use Finance\Domains\Auth\Services\AccessTokenVerifier;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuthenticateWithAccessToken
{
    public const string USER_ATTRIBUTE = 'finance.user_id';

    public function __construct(private AccessTokenVerifier $verifier)
    {
    }

    /**
     * @throws InvalidAccessTokenException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            throw new InvalidAccessTokenException();
        }

        $request->attributes->set(self::USER_ATTRIBUTE, $this->verifier->verify($token));

        return $next($request);
    }
}
