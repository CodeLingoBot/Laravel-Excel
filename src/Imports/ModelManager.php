<?php

namespace Maatwebsite\Excel\Imports;

use Throwable;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\RowValidator;
use Maatwebsite\Excel\Exceptions\RowSkippedException;
use Maatwebsite\Excel\Validators\ValidationException;

class ModelManager
{
    /**
     * @var array
     */
    private $rows = [];

    /**
     * @var RowValidator
     */
    private $validator;

    /**
     * @param RowValidator $validator
     */
    public function __construct(RowValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param int   $row
     * @param array $attributes
     */
    public function add(int $row, array $attributes)
    {
        $this->rows[$row] = $attributes;
    }

    /**
     * @param ToModel $import
     * @param bool    $massInsert
     *
     * @throws ValidationException
     */
    public function flush(ToModel $import, bool $massInsert = false)
    {
        if ($import instanceof WithValidation) {
            $this->validateRows($import);
        }

        if ($massInsert) {
            $this->massFlush($import);
        } else {
            $this->singleFlush($import);
        }

        $this->rows = [];
    }

    /**
     * @param ToModel $import
     * @param array   $attributes
     *
     * @return Model[]|Collection
     */
    public function toModels(ToModel $import, array $attributes): Collection
    {
        $model = $import->model($attributes);

        if (null !== $model) {
            return \is_array($model) ? new Collection($model) : new Collection([$model]);
        }

        return new Collection([]);
    }

    /**
     * @param ToModel $import
     */
    

    /**
     * @param ToModel $import
     */
    

    /**
     * @param Model $model
     *
     * @return Model
     */
    

    /**
     * @param WithValidation $import
     *
     * @throws ValidationException
     */
    

    /**
     * @return Collection
     */
    
}
