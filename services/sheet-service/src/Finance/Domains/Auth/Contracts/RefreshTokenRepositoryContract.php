<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Contracts;

use Carbon\CarbonImmutable;
use Finance\Domains\Auth\Models\RefreshTokenModel;

interface RefreshTokenRepositoryContract
{
    public function store(string $userIdentifier, string $tokenHash, CarbonImmutable $expiresAt): RefreshTokenModel;

    public function findValidByHash(string $tokenHash): ?RefreshTokenModel;

    public function deleteByHash(string $tokenHash): void;

    public function deleteAllForUser(string $userIdentifier): void;
}
