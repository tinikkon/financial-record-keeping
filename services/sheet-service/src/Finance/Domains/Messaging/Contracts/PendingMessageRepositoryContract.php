<?php

declare(strict_types=1);

namespace Finance\Domains\Messaging\Contracts;

use Finance\Domains\Messaging\Models\PendingMessageModel;
use Illuminate\Support\Collection;

interface PendingMessageRepositoryContract
{
    public function store(OutgoingMessage $message): PendingMessageModel;

    /**
     * @return Collection<int, PendingMessageModel>
     */
    public function oldestFirst(int $limit): Collection;

    public function forget(string $identifier): void;

    public function countAttempt(string $identifier): void;
}
