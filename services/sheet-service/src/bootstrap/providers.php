<?php

use App\Providers\AppServiceProvider;
use Finance\Domains\Auth\Providers\AuthDomainServiceProvider;
use Finance\Domains\Core\Providers\CoreDomainServiceProvider;
use Finance\Domains\Sheets\Providers\SheetsDomainServiceProvider;
use Finance\Domains\Workbooks\Providers\WorkbooksDomainServiceProvider;

return [
    AppServiceProvider::class,
    CoreDomainServiceProvider::class,
    AuthDomainServiceProvider::class,
    WorkbooksDomainServiceProvider::class,
    SheetsDomainServiceProvider::class,
];
