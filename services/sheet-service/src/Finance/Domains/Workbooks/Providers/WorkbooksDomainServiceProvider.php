<?php

declare(strict_types=1);

namespace Finance\Domains\Workbooks\Providers;

use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Finance\Domains\Workbooks\Contracts\WorkbookRepositoryContract;
use Finance\Domains\Workbooks\Repositories\WorkbookRepository;
use Illuminate\Support\ServiceProvider;

final class WorkbooksDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WorkbookRepositoryContract::class, WorkbookRepository::class);
        $this->app->tag([WorkbookRepository::class], ProvidesIndexes::class);
    }
}
