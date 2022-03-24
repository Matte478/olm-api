<?php

namespace App\GraphQL\Validators;

use Nuwave\Lighthouse\Validation\Validator;

class CreateServerInputValidator extends Validator
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
                'unique:servers,name'
            ],
            'ip_address' => [
                'required',
                'max:255'
            ],
            'api_domain' => [
                'required',
                'max:255',
                'unique:servers,api_domain'
            ],
            'websocket_port' => [
                'required',
                'integer'
            ],
            'production' => [
                'required',
                'boolean'
            ],
            'enabled' => [
                'required',
                'boolean'
            ]
        ];
    }
}
