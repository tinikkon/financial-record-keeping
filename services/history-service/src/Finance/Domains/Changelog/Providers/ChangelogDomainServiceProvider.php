<?php

declare(strict_types=1);

namespace Finance\Domains\Changelog\Providers;

use Finance\Domains\Changelog\Repositories\CellChangeRepository;
use Finance\Domains\Changelog\Repositories\CellStateRepository;
use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Illuminate\Support\ServiceProvider;

final class ChangelogDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag([CellChangeRepository::class, CellStateRepository::class], ProvidesIndexes::class);
    }
}
