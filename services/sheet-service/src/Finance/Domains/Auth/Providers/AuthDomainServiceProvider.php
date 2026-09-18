<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Providers;

use Finance\Domains\Auth\Console\CreateUserCommand;
use Finance\Domains\Auth\Contracts\RefreshTokenRepositoryContract;
use Finance\Domains\Auth\Contracts\UserRepositoryContract;
use Finance\Domains\Auth\Repositories\RefreshTokenRepository;
use Finance\Domains\Auth\Repositories\UserRepository;
use Finance\Domains\Auth\Services\AccessTokenIssuer;
use Finance\Domains\Auth\Services\RefreshTokenIssuer;
use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

final class AuthDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(UserRepositoryContract::class, UserRepository::class);
        $this->app->singleton(RefreshTokenRepositoryContract::class, RefreshTokenRepository::class);

        $this->app->tag([UserRepository::class, RefreshTokenRepository::class], ProvidesIndexes::class);

        $this->app->singleton(AccessTokenIssuer::class, fn (): AccessTokenIssuer => new AccessTokenIssuer(
            privateKey: $this->readKey('jwt.private_key_path'),
            publicKey: $this->readKey('jwt.public_key_path'),
            algorithm: (string) config('jwt.algorithm'),
            issuer: (string) config('jwt.issuer'),
            lifetimeMinutes: (int) config('jwt.access_token_minutes'),
        ));

        $this->app->singleton(RefreshTokenIssuer::class, function (): RefreshTokenIssuer {
            try {
                $repository = $this->app->make(RefreshTokenRepositoryContract::class);
            } catch (BindingResolutionException $exception) {
                throw new RuntimeException('Не удалось собрать хранилище токенов обновления', 0, $exception);
            }

            return new RefreshTokenIssuer($repository, (int) config('jwt.refresh_token_days'));
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([CreateUserCommand::class]);
        }
    }

    /**
     * Ключи лежат файлами и подкладываются в контейнер снаружи: в репозитории
     * их нет, а в переменной окружения многострочный ключ хранить неудобно.
     */
    private function readKey(string $configurationKey): string
    {
        $path = (string) config($configurationKey);
        $contents = is_readable($path) ? file_get_contents($path) : false;

        if ($contents === false) {
            throw new RuntimeException("Не найден ключ подписи токенов: {$path}");
        }

        return $contents;
    }
}
