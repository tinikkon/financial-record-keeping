<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Actions;

use Finance\Domains\Auth\Contracts\UserRepositoryContract;
use Finance\Domains\Auth\Exceptions\EmailAlreadyTakenException;
use Finance\Domains\Auth\Models\UserModel;
use Illuminate\Support\Facades\Hash;

final readonly class RegisterUserAction
{
    public function __construct(private UserRepositoryContract $users)
    {
    }

    /**
     * @throws EmailAlreadyTakenException
     */
    public function execute(string $email, string $name, string $password): UserModel
    {
        if ($this->users->findByEmail($email) !== null) {
            throw new EmailAlreadyTakenException($email);
        }

        return $this->users->create($email, $name, Hash::make($password));
    }
}
