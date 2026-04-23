<?php

namespace App\Http\Requests\Api\V1\Admin\Agents;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email'        => ['required', 'email', 'unique:agents,email'],
            'name'         => ['sometimes', 'nullable', 'string'],
            'ticket_scope' => ['sometimes', 'integer', 'between:1,3'],
            'occasional'   => ['sometimes', 'boolean'],
            'signature'    => ['sometimes', 'nullable', 'string'],
            'group_ids'    => ['sometimes', 'array'],
            'role_ids'     => ['sometimes', 'array'],
            'skill_ids'    => ['sometimes', 'array'],
        ];
    }
}
