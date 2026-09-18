<?php

use App\Providers\AppServiceProvider;
use Finance\Domains\Auth\Providers\AuthDomainServiceProvider;
use Finance\Domains\Calculation\Providers\CalculationDomainServiceProvider;
use Finance\Domains\Cells\Providers\CellsDomainServiceProvider;
use Finance\Domains\Core\Providers\CoreDomainServiceProvider;
use Finance\Domains\History\Providers\HistoryDomainServiceProvider;
use Finance\Domains\Messaging\Providers\MessagingDomainServiceProvider;
use Finance\Domains\Sheets\Providers\SheetsDomainServiceProvider;
use Finance\Domains\Workbooks\Providers\WorkbooksDomainServiceProvider;

return [
    AppServiceProvider::class,
    CoreDomainServiceProvider::class,
    AuthDomainServiceProvider::class,
    WorkbooksDomainServiceProvider::class,
    SheetsDomainServiceProvider::class,
    CellsDomainServiceProvider::class,
    CalculationDomainServiceProvider::class,
    MessagingDomainServiceProvider::class,
    HistoryDomainServiceProvider::class,
];
