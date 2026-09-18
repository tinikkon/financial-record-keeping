<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Providers;

use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Finance\Domains\Messaging\Console\ConsumeSheetEventsCommand;
use Finance\Domains\Messaging\Repositories\ProcessedMessageRepository;
use Illuminate\Support\ServiceProvider;

final class MessagingDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([ProcessedMessageRepository::class], ProvidesIndexes::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ConsumeSheetEventsCommand::class]);
        }
    }
}
