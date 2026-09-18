<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Actions;

use Finance\Domains\Auth\Contracts\RefreshTokenRepositoryContract;
use Finance\Domains\Auth\Services\RefreshTokenIssuer;

final readonly class LogoutAction
{
    public function __construct(
        private RefreshTokenRepositoryContract $refreshTokenRepository,
        private RefreshTokenIssuer $refreshTokens,
    ) {
    }

    public function execute(string $refreshToken): void
    {
        $this->refreshTokenRepository->deleteByHash($this->refreshTokens->fingerprint($refreshToken));
    }
}
