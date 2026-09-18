<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Services;

use Carbon\CarbonImmutable;

final readonly class IssuedAccessToken
{
    public function __construct(
        public string $token,
        public CarbonImmutable $expiresAt,
    ) {
    }
}
