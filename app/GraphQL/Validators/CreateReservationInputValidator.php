<?php

namespace App\GraphQL\Validators;

use Nuwave\Lighthouse\Validation\Validator;

class CreateReservationInputValidator extends Validator
{
    /**
     * Return the validation rules.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'device_id' => [
                'required',
                'exists:devices,id,deleted_at,NULL',
            ],
            'start' => [
                'required',
                'date',
                'after_or_equal:now',
            ],
            'end' => [
                'required',
                'date',
                'after:start',
            ]
        ];
    }
}
