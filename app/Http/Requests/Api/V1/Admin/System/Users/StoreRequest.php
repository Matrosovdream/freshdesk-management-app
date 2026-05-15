<?php

namespace App\Http\Requests\Api\V1\Admin\System\Users;

use App\Rules\UniqueUserPin;
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
            'pin'         => ['sometimes', 'nullable', 'string', 'digits:4', new UniqueUserPin()],
            'phone'       => ['sometimes', 'nullable', 'string', 'max:32'],
            'is_active'   => ['sometimes', 'boolean'],
            'role_ids'    => ['sometimes', 'array'],
            'role_ids.*'  => ['integer'],
            'group_ids'   => ['sometimes', 'array'],
            'group_ids.*' => ['integer'],
        ];
    }
}
