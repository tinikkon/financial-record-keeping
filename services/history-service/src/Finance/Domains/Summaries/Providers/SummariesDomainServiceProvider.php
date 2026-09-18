<?php

declare(strict_types=1);

namespace Finance\Domains\Summaries\Providers;

use Finance\Domains\Summaries\Repositories\CellStateSummaryRepository;
use Illuminate\Support\ServiceProvider;

final class SummariesDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CellStateSummaryRepository::class);
    }
}
