<?php

declare(strict_types=1);

namespace Finance\Domains\Core\Console;

use Finance\Domains\Core\Contracts\ProvidesIndexes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;
use MongoDB\Driver\Exception\Exception as MongoDriverException;

/**
 * Создаёт индексы всех коллекций.
 *
 * В MongoDB нет миграций схемы, потому что нет самой схемы. Единственное, что
 * требует подготовки, — индексы, и эта команда собирает их со всех репозиториев.
 * Повторный запуск безопасен: существующий индекс с теми же полями не создаётся заново.
 */
final class CreateIndexesCommand extends Command
{
    protected $signature = 'finance:create-indexes';

    protected $description = 'Создать индексы коллекций MongoDB';

    /**
     * @param iterable<ProvidesIndexes> $repositories
     */
    public function __construct(private readonly iterable $repositories)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $created = 0;

        /** @var Connection $connection */
        $connection = DB::connection('mongodb');

        foreach ($this->repositories as $repository) {
            $collection = $connection->getCollection($repository->collectionName());

            foreach ($repository->indexes() as $index) {
                try {
                    $collection->createIndex($index->keys, $index->options());
                    $this->line("  {$repository->collectionName()}: {$index->name}");
                    $created++;
                } catch (MongoDriverException $exception) {
                    $this->error("  {$repository->collectionName()}: {$index->name} — {$exception->getMessage()}");

                    return self::FAILURE;
                }
            }
        }

        $this->info("Индексов обработано: {$created}");

        return self::SUCCESS;
    }
}
