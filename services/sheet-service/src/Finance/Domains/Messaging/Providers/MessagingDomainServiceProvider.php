<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Providers;

use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Finance\Domains\Messaging\Console\ResendPendingMessagesCommand;
use Finance\Domains\Messaging\Contracts\EventPublisherContract;
use Finance\Domains\Messaging\Contracts\PendingMessageRepositoryContract;
use Finance\Domains\Messaging\Repositories\PendingMessageRepository;
use Finance\Domains\Messaging\Services\RabbitMqEventPublisher;
use Illuminate\Support\ServiceProvider;

final class MessagingDomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PendingMessageRepositoryContract::class, PendingMessageRepository::class);
        $this->app->tag([PendingMessageRepository::class], ProvidesIndexes::class);

        $this->app->singleton(
            EventPublisherContract::class,
            static fn (): RabbitMqEventPublisher => new RabbitMqEventPublisher(
                (array) config('messaging.rabbitmq'),
            ),
        );
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ResendPendingMessagesCommand::class]);
        }
    }
}
