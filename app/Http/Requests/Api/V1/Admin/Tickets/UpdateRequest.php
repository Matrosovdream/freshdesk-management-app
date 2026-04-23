<?php

namespace App\Http\Requests\Api\V1\Admin\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'subject'       => ['sometimes', 'string', 'max:300'],
            'description'   => ['sometimes', 'nullable', 'string'],
            'status'        => ['sometimes', 'integer', 'between:2,5'],
            'priority'      => ['sometimes', 'integer', 'between:1,4'],
            'source'        => ['sometimes', 'integer'],
            'type'          => ['sometimes', 'nullable', 'string', 'max:80'],
            'responder_id'  => ['sometimes', 'nullable', 'integer'],
            'group_id'      => ['sometimes', 'nullable', 'integer'],
            'company_id'    => ['sometimes', 'nullable', 'integer'],
            'spam'          => ['sometimes', 'boolean'],
            'tags'          => ['sometimes', 'array'],
            'cc_emails'     => ['sometimes', 'array'],
            'due_by'        => ['sometimes', 'nullable', 'date'],
            'fr_due_by'     => ['sometimes', 'nullable', 'date'],
            'custom_fields' => ['sometimes', 'array'],
        ];
    }
}
