<?php

declare(strict_types=1);

namespace Finance\Domains\History\Providers;

use Finance\Domains\History\Contracts\SheetHistoryDriverContract;
use Finance\Domains\History\Drivers\SheetHistoryHttpDriver;
use Illuminate\Support\ServiceProvider;

final class HistoryDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            SheetHistoryDriverContract::class,
            static fn (): SheetHistoryHttpDriver => new SheetHistoryHttpDriver(
                (string) config('services.history.url'),
                (int) config('services.history.timeout'),
            ),
        );
    }
}
