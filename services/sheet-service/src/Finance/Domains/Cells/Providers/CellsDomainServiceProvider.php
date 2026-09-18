<?php

declare(strict_types=1);

namespace Finance\Domains\Cells\Providers;

use Finance\Domains\Cells\Contracts\CellRepositoryContract;
use Finance\Domains\Cells\Repositories\CellRepository;
use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Illuminate\Support\ServiceProvider;

final class CellsDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CellRepositoryContract::class, CellRepository::class);
        $this->app->tag([CellRepository::class], ProvidesIndexes::class);
    }
}
