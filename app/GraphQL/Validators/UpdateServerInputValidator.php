<?php

namespace App\GraphQL\Validators;

use Illuminate\Validation\Rule;
use Nuwave\Lighthouse\Validation\Validator;

class UpdateServerInputValidator extends Validator
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
                'exists:servers,id,deleted_at,NULL'
            ],
            'name' => [
                'required',
                'max:255',
                Rule::unique('servers', 'name')->ignore($this->arg('id'), 'id')
            ],
            'ip_address' => [
                'required',
                'max:255',
            ],
            'api_domain' => [
                'required',
                'max:255',
                Rule::unique('servers', 'api_domain')->ignore($this->arg('id'), 'id')
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
