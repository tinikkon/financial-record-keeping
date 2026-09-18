<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Repositories;

use Carbon\CarbonImmutable;
use Finance\Domains\Auth\Contracts\RefreshTokenRepositoryContract;
use Finance\Domains\Auth\Models\RefreshTokenModel;
use Finance\Domains\Core\Contracts\IndexDefinition;
use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Finance\Domains\Core\Repositories\AbstractMongoRepository;

final class RefreshTokenRepository extends AbstractMongoRepository implements ProvidesIndexes, RefreshTokenRepositoryContract
{
    public function store(string $userIdentifier, string $tokenHash, CarbonImmutable $expiresAt): RefreshTokenModel
    {
        $token = new RefreshTokenModel();
        $token->fill([
            'user_id' => $userIdentifier,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
        ]);
        $token->save();

        return $token;
    }

    public function findValidByHash(string $tokenHash): ?RefreshTokenModel
    {
        $token = $this->query()
            ->where('token_hash', $tokenHash)
            ->where('expires_at', '>', CarbonImmutable::now())
            ->first();

        return $token instanceof RefreshTokenModel ? $token : null;
    }

    public function deleteByHash(string $tokenHash): void
    {
        $this->query()->where('token_hash', $tokenHash)->delete();
    }

    public function deleteAllForUser(string $userIdentifier): void
    {
        $this->query()->where('user_id', $userIdentifier)->delete();
    }

    public function collectionName(): string
    {
        return 'refresh_tokens';
    }

    public function indexes(): array
    {
        return [
            new IndexDefinition(name: 'refresh_tokens_hash_unique', keys: ['token_hash' => 1], unique: true),
            new IndexDefinition(name: 'refresh_tokens_user', keys: ['user_id' => 1]),
            // Просроченные токены убирает сама база: индекс со сроком жизни удаляет
            // документ, когда время в поле проходит. Чистить их вручную не нужно.
            new IndexDefinition(name: 'refresh_tokens_expiry', keys: ['expires_at' => 1], expireAfterSeconds: 0),
        ];
    }

    protected function modelClass(): string
    {
        return RefreshTokenModel::class;
    }
}
