<?php

declare(strict_types=1);

namespace Finance\Domains\Core\Repositories;

use Finance\Domains\Core\Models\MongoModel;
use Illuminate\Support\Facades\DB;
use MongoDB\Driver\Session;
use MongoDB\Laravel\Connection;
use MongoDB\Collection;
use MongoDB\Laravel\Eloquent\Builder;

/**
 * Общая часть репозиториев: доступ к запросам модели и к самой коллекции.
 *
 * Прямой доступ к коллекции нужен там, где возможностей Eloquent не хватает:
 * создание индексов, атомарные операции, конвейеры агрегации.
 */
abstract class AbstractMongoRepository
{
    /**
     * @return class-string<MongoModel>
     */
    abstract protected function modelClass(): string;

    /**
     * @return Builder<MongoModel>
     */
    protected function query(): Builder
    {
        $modelClass = $this->modelClass();

        /** @var Builder<MongoModel> $builder */
        $builder = $modelClass::query();

        return $builder;
    }

    protected function collection(): Collection
    {
        $modelClass = $this->modelClass();
        $model = new $modelClass();

        /** @var Connection $connection */
        $connection = DB::connection('mongodb');

        return $connection->getCollection($model->getTable());
    }

    /**
     * Настройки, привязывающие сырую операцию к текущей транзакции.
     *
     * Запросы через коллекцию идут мимо сессии, которую открыл Laravel: запись
     * окажется вне транзакции, а чтение внутри неё этой записи не увидит.
     *
     * @return array{session?: Session}
     */
    protected function sessionOptions(): array
    {
        /** @var Connection $connection */
        $connection = DB::connection('mongodb');
        $session = $connection->getSession();

        return $session === null ? [] : ['session' => $session];
    }
}
