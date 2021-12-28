<?php

namespace App\GraphQL\Validators;

use Nuwave\Lighthouse\Validation\Validator;

class UpdateReservationInputValidator extends Validator
{
    /**
     * Return the validation rules.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'id' => [
                'required',
                'exists:reservations,id',
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
