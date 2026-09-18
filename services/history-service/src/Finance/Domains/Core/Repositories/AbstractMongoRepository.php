<?php

declare(strict_types=1);

namespace Finance\Domains\Core\Repositories;

use Finance\Domains\Core\Models\MongoModel;
use Illuminate\Support\Facades\DB;
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

    protected function query(): Builder
    {
        $modelClass = $this->modelClass();

        /** @var Builder $builder */
        $builder = $modelClass::query();

        return $builder;
    }

    protected function collection(): Collection
    {
        $modelClass = $this->modelClass();
        $model = new $modelClass();

        /** @var Collection $collection */
        $collection = DB::connection('mongodb')->getCollection($model->getTable());

        return $collection;
    }
}
