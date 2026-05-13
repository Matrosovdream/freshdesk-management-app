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
            'job_title'    => ['sometimes', 'nullable', 'string'],
            'language'     => ['sometimes', 'nullable', 'string'],
            'time_zone'    => ['sometimes', 'nullable', 'string'],
            'type'         => ['sometimes', 'nullable', 'in:support_agent,field_agent,collaborator'],
            'ticket_scope' => ['sometimes', 'integer', 'between:1,3'],
            'occasional'   => ['sometimes', 'boolean'],
            'available'    => ['sometimes', 'boolean'],
            'signature'    => ['sometimes', 'nullable', 'string'],
            'group_ids'    => ['sometimes', 'array'],
            'role_ids'     => ['sometimes', 'array'],
            'skill_ids'    => ['sometimes', 'array'],
        ];
    }
}
