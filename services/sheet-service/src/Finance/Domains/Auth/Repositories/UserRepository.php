<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Repositories;

use Finance\Domains\Auth\Contracts\UserRepositoryContract;
use Finance\Domains\Auth\Models\UserModel;
use Finance\Domains\Core\Contracts\IndexDefinition;
use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Finance\Domains\Core\Repositories\AbstractMongoRepository;

final class UserRepository extends AbstractMongoRepository implements ProvidesIndexes, UserRepositoryContract
{
    public function findByEmail(string $email): ?UserModel
    {
        $user = $this->query()->where('email', mb_strtolower($email))->first();

        return $user instanceof UserModel ? $user : null;
    }

    public function findByIdentifier(string $identifier): ?UserModel
    {
        $user = $this->query()->where('_id', $identifier)->first();

        return $user instanceof UserModel ? $user : null;
    }

    public function create(string $email, string $name, string $passwordHash): UserModel
    {
        $user = new UserModel();
        $user->fill([
            'email' => mb_strtolower($email),
            'name' => $name,
            'password_hash' => $passwordHash,
        ]);
        $user->save();

        return $user;
    }

    public function collectionName(): string
    {
        return 'users';
    }

    public function indexes(): array
    {
        return [
            // Уникальность почты обеспечивает база, а не проверка в коде: между
            // проверкой и вставкой всегда есть зазор, в который проскакивает второй запрос.
            new IndexDefinition(name: 'users_email_unique', keys: ['email' => 1], unique: true),
        ];
    }

    protected function modelClass(): string
    {
        return UserModel::class;
    }
}
