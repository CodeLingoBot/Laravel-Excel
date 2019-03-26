<?php

namespace Maatwebsite\Excel\Validators;

use Illuminate\Support\Str;
use Illuminate\Contracts\Validation\Factory;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Exceptions\RowSkippedException;
use Illuminate\Validation\ValidationException as IlluminateValidationException;

class RowValidator
{
    /**
     * @var Factory
     */
    private $validator;

    /**
     * @param Factory $validator
     */
    public function __construct(Factory $validator)
    {
        $this->validator = $validator;
    }

    /**
     * @param array          $rows
     * @param WithValidation $import
     *
     * @throws ValidationException
     * @throws RowSkippedException
     */
    public function validate(array $rows, WithValidation $import)
    {
        $rules      = $this->rules($import);
        $messages   = $this->messages($import);
        $attributes = $this->attributes($import);

        try {
            $this->validator->make($rows, $rules, $messages, $attributes)->validate();
        } catch (IlluminateValidationException $e) {
            $failures = [];
            foreach ($e->errors() as $attribute => $messages) {
                $row           = strtok($attribute, '.');
                $attributeName = strtok('');
                $attributeName = $attributes['*.' . $attributeName] ?? $attributeName;

                $failures[] = new Failure(
                    $row,
                    $attributeName,
                    str_replace($attribute, $attributeName, $messages),
                    $rows[$row]
                );
            }

            if ($import instanceof SkipsOnFailure) {
                $import->onFailure(...$failures);
                throw new RowSkippedException(...$failures);
            }

            throw new ValidationException(
                $e,
                $failures
            );
        }
    }

    /**
     * @param WithValidation $import
     *
     * @return array
     */
    

    /**
     * @param WithValidation $import
     *
     * @return array
     */
    

    /**
     * @param WithValidation $import
     *
     * @return array
     */
    

    /**
     * @param array $elements
     *
     * @return array
     */
    

    /**
     * @param string|object|callable|array $rules
     *
     * @return string|array
     */
    
}
