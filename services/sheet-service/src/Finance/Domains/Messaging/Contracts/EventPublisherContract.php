<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Contracts;

use Finance\Domains\Messaging\Services\MessagePublishingFailedException;

interface EventPublisherContract
{
    /**
     * @param array<string, mixed> $payload
     *
     * @throws MessagePublishingFailedException
     */
    public function publish(string $routingKey, string $messageIdentifier, array $payload): void;
}
