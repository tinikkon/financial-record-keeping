<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Actions;

use Carbon\CarbonImmutable;
use Finance\Domains\Auth\Models\UserModel;

/**
 * Результат успешного входа: пользователь и пара токенов.
 */
final readonly class AuthenticatedSession
{
    public function __construct(
        public UserModel $user,
        public string $accessToken,
        public CarbonImmutable $accessTokenExpiresAt,
        public string $refreshToken,
    ) {
    }
}
