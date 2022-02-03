<?php

namespace App\GraphQL\Validators;

use Nuwave\Lighthouse\Validation\Validator;

class CreateUserExperimentInputValidator extends Validator
{
    /**
     * Return the validation rules.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'experiment_id' => [
                'required',
                'exists:experiments,id,deleted_at,NULL',
            ],
            'input' => [
                'nullable',
                'array'
            ],
            'output' => [
                'nullable',
            ],
            'note' => [
                'nullable',
            ],
            'simulation_time' => [
                'nullable',
                'integer',
            ],
            'sampling_rate' => [
                'nullable',
                'integer',
            ],
        ];
    }
}
