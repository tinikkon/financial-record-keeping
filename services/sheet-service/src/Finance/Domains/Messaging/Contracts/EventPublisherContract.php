<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Contracts;

use Finance\Domains\Messaging\Services\MessagePublishingFailedException;

interface EventPublisherContract
{
    /**
     * @throws MessagePublishingFailedException
     */
    public function publish(OutgoingMessage $message): void;
}
