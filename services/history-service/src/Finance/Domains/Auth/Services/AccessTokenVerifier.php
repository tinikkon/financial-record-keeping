<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Services;

use DomainException;
use Finance\Domains\Auth\Exceptions\InvalidAccessTokenException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Проверяет подпись токена доступа открытым ключом.
 *
 * Обращения к сервису таблиц не происходит: недоступность соседа не должна
 * мешать читать историю, да и лишний сетевой вызов на каждый запрос ни к чему.
 */
final readonly class AccessTokenVerifier
{
    public function __construct(
        private string $publicKey,
        private string $algorithm,
    ) {
    }

    /**
     * @throws InvalidAccessTokenException
     */
    public function verify(string $token): string
    {
        try {
            $claims = JWT::decode($token, new Key($this->publicKey, $this->algorithm));
        } catch (DomainException|InvalidArgumentException|UnexpectedValueException $exception) {
            throw new InvalidAccessTokenException('Токен недействителен или истёк', $exception);
        }

        if (! isset($claims->sub)) {
            throw new InvalidAccessTokenException('В токене не указан пользователь');
        }

        return (string) $claims->sub;
    }
}
