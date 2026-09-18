<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Providers;

use Finance\Domains\Auth\Services\AccessTokenVerifier;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

final class AuthDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AccessTokenVerifier::class, function (): AccessTokenVerifier {
            $path = (string) config('jwt.public_key_path');
            $key = is_readable($path) ? file_get_contents($path) : false;

            if ($key === false) {
                throw new RuntimeException("Не найден открытый ключ проверки токенов: {$path}");
            }

            return new AccessTokenVerifier($key, (string) config('jwt.algorithm'));
        });
    }
}
