<?php

namespace App\GraphQL\Validators;

use Illuminate\Validation\Rule;
use Nuwave\Lighthouse\Validation\Validator;

class UpdateSchemaInputValidator extends Validator
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
                'exists:schemas,id'
            ],
            'name' => [
                'required',
                'max:255',
                Rule::unique('schemas', 'name')->ignore($this->arg('id'), 'id')
            ],
            'device_type_id' => [
                'required',
                'exists:device_types,id',
            ],
            'software_id' => [
                'required',
                'exists:software,id',
            ],
            'note' => [
                'nullable',
                'string'
            ],
            'arguments' => [
                'array',
            ],
            'arguments.*.name' => [
                'required',
                'max:255',
            ],
            'arguments.*.label' => [
                'required',
                'max:255',
            ],
            'arguments.*.default_value' => [
                'nullable',
                'numeric',
            ],
            'arguments.*.row' => [
                'integer',
            ],
            'arguments.*.order' => [
                'integer',
            ],
            'arguments.*.options' => [
                'array',
            ],
            'arguments.*.options.*.name' => [
                'required',
                'max:255',
            ],
            'arguments.*.options.*.value' => [
                'required',
                'numeric',
            ],
            'schema' => [
                'mimetypes:text/xml,application/octet-stream'
            ],
            'preview' => [
                'mimetypes:image/jpg,image/jpeg,image/png',
            ],
        ];
    }
}
