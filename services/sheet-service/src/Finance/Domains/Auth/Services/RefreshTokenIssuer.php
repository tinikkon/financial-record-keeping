<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Services;

use Carbon\CarbonImmutable;
use Finance\Domains\Auth\Contracts\RefreshTokenRepositoryContract;
use Random\RandomException;
use RuntimeException;

/**
 * Выдаёт токены обновления. В базу попадает только отпечаток токена,
 * сам токен существует лишь у владельца.
 */
final readonly class RefreshTokenIssuer
{
    private const int TOKEN_BYTES = 48;

    public function __construct(
        private RefreshTokenRepositoryContract $refreshTokens,
        private int $lifetimeDays,
    ) {
    }

    public function issue(string $userIdentifier): string
    {
        try {
            $token = bin2hex(random_bytes(self::TOKEN_BYTES));
        } catch (RandomException $exception) {
            throw new RuntimeException('Не удалось получить случайные данные для токена', 0, $exception);
        }

        $this->refreshTokens->store(
            $userIdentifier,
            $this->fingerprint($token),
            CarbonImmutable::now()->addDays($this->lifetimeDays),
        );

        return $token;
    }

    public function fingerprint(string $token): string
    {
        return hash('sha256', $token);
    }
}
