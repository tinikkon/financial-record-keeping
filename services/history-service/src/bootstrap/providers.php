<?php

use App\Providers\AppServiceProvider;
use Finance\Domains\Auth\Providers\AuthDomainServiceProvider;
use Finance\Domains\Changelog\Providers\ChangelogDomainServiceProvider;
use Finance\Domains\Core\Providers\CoreDomainServiceProvider;
use Finance\Domains\Messaging\Providers\MessagingDomainServiceProvider;
use Finance\Domains\Summaries\Providers\SummariesDomainServiceProvider;

return [
    AppServiceProvider::class,
    CoreDomainServiceProvider::class,
    AuthDomainServiceProvider::class,
    ChangelogDomainServiceProvider::class,
    MessagingDomainServiceProvider::class,
    SummariesDomainServiceProvider::class,
];
