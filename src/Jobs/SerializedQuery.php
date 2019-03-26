<?php

namespace Maatwebsite\Excel\Jobs;

use Closure;
use Illuminate\Support\Facades\DB;
use Opis\Closure\SerializableClosure;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class SerializedQuery
{
    /**
     * @var string
     */
    public $query;

    /**
     * @var array
     */
    public $bindings;

    /**
     * @var string
     */
    public $connection;

    /**
     * @var string|null
     */
    public $model;

    /**
     * @var array
     */
    public $with = [];

    /**
     * @param Builder|Relation|EloquentBuilder $builder
     */
    public function __construct($builder)
    {
        $this->query      = $builder->toSql();
        $this->bindings   = $builder->getBindings();
        $this->connection = $builder->getConnection()->getName();
        $this->with       = $this->serializeEagerLoads($builder);

        if ($builder instanceof EloquentBuilder || $builder instanceof Relation) {
            $this->model = get_class($builder->getModel());
        }
    }

    /**
     * @return array
     */
    public function execute()
    {
        $connection = DB::connection($this->connection);

        return $this->hydrate(
            $connection->select($this->query, $this->bindings)
        );
    }

    /**
     * @param array $items
     *
     * @return array
     */
    public function hydrate(array $items)
    {
        if (!$instance = $this->newModelInstance()) {
            return $items;
        }

        $models = array_map(function ($item) use ($instance) {
            return $instance->newFromBuilder($item);
        }, $items);

        if (!empty($this->with)) {
            $instance
                ->newQuery()
                ->setEagerLoads($this->eagerLoads())
                ->eagerLoadRelations($models);
        }

        return $models;
    }

    /**
     * @return Model|null
     */
    

    /**
     * @param Builder|\Illuminate\Database\Eloquent\Builder $builder
     *
     * @return array
     */
    

    /**
     * @return array
     */
    
}
