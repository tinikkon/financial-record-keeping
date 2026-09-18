<?php

declare(strict_types=1);

namespace Finance\Domains\Core\Contracts;

/**
 * Репозиторий, который знает, какие индексы нужны его коллекции.
 */
interface ProvidesIndexes
{
    public function collectionName(): string;

    /**
     * @return list<IndexDefinition>
     */
    public function indexes(): array;
}
