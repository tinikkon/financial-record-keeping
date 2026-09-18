<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Resources;

use Finance\Domains\Auth\Models\UserModel;

final readonly class UserResource
{
    /**
     * @return array<string, string>
     */
    public static function toArray(UserModel $user): array
    {
        return [
            'id' => $user->identifier(),
            'email' => $user->email,
            'name' => $user->name,
        ];
    }
}
