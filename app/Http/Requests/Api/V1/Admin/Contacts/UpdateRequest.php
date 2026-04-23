<?php

namespace App\Http\Requests\Api\V1\Admin\Contacts;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'string', 'max:160'],
            'email'         => ['sometimes', 'nullable', 'email'],
            'phone'         => ['sometimes', 'nullable', 'string'],
            'mobile'        => ['sometimes', 'nullable', 'string'],
            'company_id'    => ['sometimes', 'nullable', 'integer'],
            'tags'          => ['sometimes', 'array'],
            'custom_fields' => ['sometimes', 'array'],
            'active'        => ['sometimes', 'boolean'],
        ];
    }
}
