<?php

namespace App\Http\Requests\Api\V1\Admin\System\Managers;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email'       => ['required', 'email', 'unique:users,email'],
            'name'        => ['sometimes', 'nullable', 'string', 'max:120'],
            'password'    => ['sometimes', 'nullable', 'string', 'min:8'],
            'is_active'   => ['sometimes', 'boolean'],
            'group_ids'   => ['sometimes', 'array'],
            'group_ids.*' => ['integer'],
        ];
    }
}
