<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTemplateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'             => ['sometimes', 'string', 'max:150'],
            'channel'          => ['sometimes', 'in:email,whatsapp,push'],
            'subject'          => ['nullable', 'string', 'max:190'],
            'body'             => ['sometimes', 'string'],
            'variables_schema' => ['nullable', 'array'],
            'variables_schema.required' => ['array'],
            'variables_schema.optional' => ['array'],
            'variables_schema.rules'    => ['array'],
            'is_active'        => ['sometimes', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
