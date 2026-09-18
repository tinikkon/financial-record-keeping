<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Actions;

use Finance\Domains\Auth\Contracts\RefreshTokenRepositoryContract;
use Finance\Domains\Auth\Contracts\UserRepositoryContract;
use Finance\Domains\Auth\Exceptions\InvalidRefreshTokenException;
use Finance\Domains\Auth\Services\AccessTokenIssuer;
use Finance\Domains\Auth\Services\RefreshTokenIssuer;

/**
 * Обновляет пару токенов.
 *
 * Старый токен обновления удаляется сразу: каждый живёт ровно одно применение.
 * Если тот же токен придёт повторно, он уже не найдётся — так видно попытку
 * воспользоваться перехваченной копией.
 */
final readonly class RefreshTokensAction
{
    public function __construct(
        private RefreshTokenRepositoryContract $refreshTokenRepository,
        private UserRepositoryContract $users,
        private AccessTokenIssuer $accessTokens,
        private RefreshTokenIssuer $refreshTokens,
    ) {
    }

    /**
     * @throws InvalidRefreshTokenException
     */
    public function execute(string $refreshToken): AuthenticatedSession
    {
        $fingerprint = $this->refreshTokens->fingerprint($refreshToken);
        $stored = $this->refreshTokenRepository->findValidByHash($fingerprint);

        if ($stored === null) {
            throw new InvalidRefreshTokenException();
        }

        $user = $this->users->findByIdentifier($stored->user_id);

        if ($user === null) {
            throw new InvalidRefreshTokenException();
        }

        $this->refreshTokenRepository->deleteByHash($fingerprint);

        $accessToken = $this->accessTokens->issue($user);

        return new AuthenticatedSession(
            user: $user,
            accessToken: $accessToken->token,
            accessTokenExpiresAt: $accessToken->expiresAt,
            refreshToken: $this->refreshTokens->issue($user->identifier()),
        );
    }
}
