<?php

declare(strict_types=1);

namespace Finance\Domains\Auth\Console;

use Finance\Domains\Auth\Actions\RegisterUserAction;
use Finance\Domains\Auth\Exceptions\EmailAlreadyTakenException;
use Illuminate\Console\Command;

/**
 * Заводит пользователя из консоли. Открытой регистрации в приложении нет:
 * пользователей двое, и заводятся они руками.
 */
final class CreateUserCommand extends Command
{
    protected $signature = 'finance:create-user {email} {name} {--password=}';

    protected $description = 'Завести пользователя';

    public function handle(RegisterUserAction $registerUser): int
    {
        $password = $this->option('password');

        if (! is_string($password) || $password === '') {
            $password = $this->secret('Пароль');
        }

        if (! is_string($password) || mb_strlen($password) < 8) {
            $this->error('Пароль должен быть не короче восьми символов');

            return self::FAILURE;
        }

        try {
            $user = $registerUser->execute(
                (string) $this->argument('email'),
                (string) $this->argument('name'),
                $password,
            );
        } catch (EmailAlreadyTakenException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Пользователь заведён: {$user->email}");

        return self::SUCCESS;
    }
}
