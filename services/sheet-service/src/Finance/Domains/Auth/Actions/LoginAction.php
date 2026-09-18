<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Actions;

use Finance\Domains\Auth\Contracts\UserRepositoryContract;
use Finance\Domains\Auth\Exceptions\InvalidCredentialsException;
use Finance\Domains\Auth\Services\AccessTokenIssuer;
use Finance\Domains\Auth\Services\RefreshTokenIssuer;
use Illuminate\Support\Facades\Hash;

final readonly class LoginAction
{
    /**
     * Отпечаток несуществующего пароля нужного формата: сверка с ним занимает
     * столько же времени, сколько сверка с настоящим.
     */
    private const string DUMMY_PASSWORD_HASH = '$2y$12$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';

    public function __construct(
        private UserRepositoryContract $users,
        private AccessTokenIssuer $accessTokens,
        private RefreshTokenIssuer $refreshTokens,
    ) {
    }

    /**
     * @throws InvalidCredentialsException
     */
    public function execute(string $email, string $password): AuthenticatedSession
    {
        $user = $this->users->findByEmail($email);

        if ($user === null) {
            // Пароль сверяется даже с заведомо несуществующим пользователем:
            // иначе по времени ответа можно было бы узнать, какие почты заведены.
            password_verify($password, self::DUMMY_PASSWORD_HASH);

            throw new InvalidCredentialsException();
        }

        if (! Hash::check($password, $user->password_hash)) {
            throw new InvalidCredentialsException();
        }

        $accessToken = $this->accessTokens->issue($user);

        return new AuthenticatedSession(
            user: $user,
            accessToken: $accessToken->token,
            accessTokenExpiresAt: $accessToken->expiresAt,
            refreshToken: $this->refreshTokens->issue($user->identifier()),
        );
    }
}
