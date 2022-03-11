<?php

namespace App\GraphQL\Validators;

use Nuwave\Lighthouse\Validation\Validator;

class RunUserExperimentInputValidator extends Validator
{
    /**
     * Return the validation rules.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'user_experiment_id' => [
                'exists:user_experiments,id',
            ],
            'experiment_id' => [
                'required',
                'exists:experiments,id,deleted_at,NULL',
            ],
            'input' => [
                'nullable',
                'array'
            ],
            'software_id' => [
                'required',
                'exists:software,id',
            ],
            'schema_id' => [
//                'required',
                'exists:schemas,id',
            ],
//            'output' => [
//                'nullable',
//            ],
            'note' => [
                'nullable',
            ],
        ];
    }
}
