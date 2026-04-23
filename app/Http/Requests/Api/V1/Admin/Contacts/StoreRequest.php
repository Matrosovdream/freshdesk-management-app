<?php

namespace App\Http\Requests\Api\V1\Admin\Contacts;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:160'],
            'email'         => ['nullable', 'email'],
            'phone'         => ['nullable', 'string'],
            'mobile'        => ['nullable', 'string'],
            'company_id'    => ['nullable', 'integer'],
            'tags'          => ['sometimes', 'array'],
            'custom_fields' => ['sometimes', 'array'],
        ];
    }
}
