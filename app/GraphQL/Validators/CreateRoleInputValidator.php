<?php

namespace App\GraphQL\Validators;

use Illuminate\Validation\Rule;
use Nuwave\Lighthouse\Validation\Validator;

class CreateRoleInputValidator extends Validator
{
    /**
     * Return the validation rules.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'max:255',
                Rule::unique('roles', 'name')
            ],
            'permissions' => [
                'required',
                'array'
            ],
            'permissions.*' => [
                'integer',
                'exists:Spatie\Permission\Models\Permission,id'
            ],
        ];
    }
}
