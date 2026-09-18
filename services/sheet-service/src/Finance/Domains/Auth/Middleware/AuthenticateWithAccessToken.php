<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Middleware;

use Closure;
use Finance\Domains\Auth\Contracts\UserRepositoryContract;
use Finance\Domains\Auth\Exceptions\InvalidAccessTokenException;
use Finance\Domains\Auth\Models\UserModel;
use Finance\Domains\Auth\Services\AccessTokenIssuer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Пропускает запрос только с действующим токеном доступа и кладёт пользователя
 * в запрос, чтобы контроллеры не занимались разбором заголовка.
 */
final readonly class AuthenticateWithAccessToken
{
    public const string USER_ATTRIBUTE = 'finance.user';

    public function __construct(
        private AccessTokenIssuer $accessTokens,
        private UserRepositoryContract $users,
    ) {
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

        $payload = $this->accessTokens->parse($token);
        $user = $this->users->findByIdentifier($payload->userIdentifier);

        if ($user === null) {
            throw new InvalidAccessTokenException('Пользователь не найден');
        }

        $request->attributes->set(self::USER_ATTRIBUTE, $user);

        // Проверка доступа к каналам вещания берёт пользователя через штатный
        // механизм Laravel, а не из атрибутов, поэтому он задаётся и там.
        $request->setUserResolver(static fn (): UserModel => $user);

        return $next($request);
    }
}
