<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RenderTemplateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'variables' => ['required', 'array'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
