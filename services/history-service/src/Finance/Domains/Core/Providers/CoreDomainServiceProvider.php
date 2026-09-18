<?php

declare(strict_types=1);

namespace Finance\Domains\Core\Providers;

use Finance\Domains\Core\Console\CreateIndexesCommand;
use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Собирает репозитории, объявляющие индексы, в один список для команды создания индексов.
 */
final class CoreDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            CreateIndexesCommand::class,
            static fn (Application $application): CreateIndexesCommand => new CreateIndexesCommand(
                $application->tagged(ProvidesIndexes::class),
            ),
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([CreateIndexesCommand::class]);
        }
    }
}
