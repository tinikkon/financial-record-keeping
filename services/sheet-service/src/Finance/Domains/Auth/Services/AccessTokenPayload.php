<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Services;

final readonly class AccessTokenPayload
{
    public function __construct(
        public string $userIdentifier,
        public string $email,
    ) {
    }
}
