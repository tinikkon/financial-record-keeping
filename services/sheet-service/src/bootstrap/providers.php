<?php

use App\Providers\AppServiceProvider;
use Finance\Domains\Auth\Providers\AuthDomainServiceProvider;
use Finance\Domains\Core\Providers\CoreDomainServiceProvider;

return [
    AppServiceProvider::class,
    CoreDomainServiceProvider::class,
    AuthDomainServiceProvider::class,
];
