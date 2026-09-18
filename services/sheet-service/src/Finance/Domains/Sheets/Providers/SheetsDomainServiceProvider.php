<?php

declare(strict_types=1);

namespace Finance\Domains\Sheets\Providers;

use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Finance\Domains\Sheets\Contracts\SheetRepositoryContract;
use Finance\Domains\Sheets\Repositories\SheetRepository;
use Illuminate\Support\ServiceProvider;

final class SheetsDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SheetRepositoryContract::class, SheetRepository::class);
        $this->app->tag([SheetRepository::class], ProvidesIndexes::class);
    }
}
