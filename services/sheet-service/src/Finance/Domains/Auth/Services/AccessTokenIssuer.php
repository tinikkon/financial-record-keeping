<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Services;

use Carbon\CarbonImmutable;
use Finance\Domains\Auth\Exceptions\InvalidAccessTokenException;
use Finance\Domains\Auth\Models\UserModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use DomainException;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Выдаёт и проверяет токены доступа.
 *
 * Подпись асимметричная: закрытый ключ есть только здесь, поэтому выпустить
 * токен может лишь этот сервис. Сервису истории достаточно открытого ключа —
 * он проверяет подпись у себя, не дёргая сеть на каждый запрос.
 */
final readonly class AccessTokenIssuer
{
    public function __construct(
        private string $privateKey,
        private string $publicKey,
        private string $algorithm,
        private string $issuer,
        private int $lifetimeMinutes,
    ) {
    }

    public function issue(UserModel $user): IssuedAccessToken
    {
        $issuedAt = CarbonImmutable::now();
        $expiresAt = $issuedAt->addMinutes($this->lifetimeMinutes);

        $token = JWT::encode([
            'iss' => $this->issuer,
            'sub' => $user->identifier(),
            'email' => $user->email,
            'iat' => $issuedAt->getTimestamp(),
            'exp' => $expiresAt->getTimestamp(),
        ], $this->privateKey, $this->algorithm);

        return new IssuedAccessToken($token, $expiresAt);
    }

    /**
     * @throws InvalidAccessTokenException
     */
    public function parse(string $token): AccessTokenPayload
    {
        try {
            $claims = JWT::decode($token, new Key($this->publicKey, $this->algorithm));
        } catch (DomainException|InvalidArgumentException|UnexpectedValueException $exception) {
            throw new InvalidAccessTokenException('Токен недействителен или истёк', $exception);
        }

        if (! isset($claims->sub, $claims->email)) {
            throw new InvalidAccessTokenException('В токене не хватает данных');
        }

        return new AccessTokenPayload((string) $claims->sub, (string) $claims->email);
    }
}
