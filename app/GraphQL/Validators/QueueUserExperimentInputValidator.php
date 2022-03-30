<?php

namespace App\GraphQL\Validators;

use Nuwave\Lighthouse\Validation\Validator;

class QueueUserExperimentInputValidator extends Validator
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
            'software_id' => [
                'required',
                'exists:software,id',
            ],
            'schema_id' => [
                'exists:schemas,id',
            ],
        ];
    }
}
