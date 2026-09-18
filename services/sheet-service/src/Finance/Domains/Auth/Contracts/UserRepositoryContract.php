<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Contracts;

use Finance\Domains\Auth\Models\UserModel;

interface UserRepositoryContract
{
    public function findByEmail(string $email): ?UserModel;

    public function findByIdentifier(string $identifier): ?UserModel;

    public function create(string $email, string $name, string $passwordHash): UserModel;
}
