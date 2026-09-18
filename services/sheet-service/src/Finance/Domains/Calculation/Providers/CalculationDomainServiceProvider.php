<?php

declare(strict_types=1);

namespace Finance\Domains\Calculation\Providers;

use Finance\FormulaEngine\FormulaEngine;
use Finance\FormulaEngine\Recalculation\RecalculationPlanner;
use Illuminate\Support\ServiceProvider;

final class CalculationDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FormulaEngine::class, static fn (): FormulaEngine => FormulaEngine::create());
        $this->app->singleton(RecalculationPlanner::class);
    }
}
