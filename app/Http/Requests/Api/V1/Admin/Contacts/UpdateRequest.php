<?php

namespace App\Http\Requests\Api\V1\Admin\Contacts;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'               => ['sometimes', 'string', 'max:160'],
            'email'              => ['sometimes', 'nullable', 'email'],
            'phone'              => ['sometimes', 'nullable', 'string'],
            'mobile'             => ['sometimes', 'nullable', 'string'],
            'twitter_id'         => ['sometimes', 'nullable', 'string'],
            'unique_external_id' => ['sometimes', 'nullable', 'string'],
            'company_id'         => ['sometimes', 'nullable', 'integer'],
            'job_title'          => ['sometimes', 'nullable', 'string'],
            'language'           => ['sometimes', 'nullable', 'string'],
            'time_zone'          => ['sometimes', 'nullable', 'string'],
            'address'            => ['sometimes', 'nullable', 'string'],
            'tags'               => ['sometimes', 'array'],
            'custom_fields'      => ['sometimes', 'array'],
            'active'             => ['sometimes', 'boolean'],
            'view_all_tickets'   => ['sometimes', 'boolean'],
            'blocked'            => ['sometimes', 'boolean'],
        ];
    }
}
