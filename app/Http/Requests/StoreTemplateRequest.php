<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'key'              => ['required', 'string', 'max:120', 'alpha_dash', 'unique:templates,key'],
            'name'             => ['required', 'string', 'max:150'],
            'channel'          => ['required', 'in:email,whatsapp,push'],
            'subject'          => ['nullable', 'string', 'max:190'],
            'body'             => ['required', 'string'],
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
