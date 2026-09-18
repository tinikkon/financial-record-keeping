<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Resources;

use Finance\Domains\Auth\Actions\AuthenticatedSession;

final readonly class AuthenticatedSessionResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(AuthenticatedSession $session): array
    {
        return [
            'user' => UserResource::toArray($session->user),
            'accessToken' => $session->accessToken,
            'accessTokenExpiresAt' => $session->accessTokenExpiresAt->toIso8601String(),
            'refreshToken' => $session->refreshToken,
        ];
    }
}
