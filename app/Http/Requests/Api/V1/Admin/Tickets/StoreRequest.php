<?php

namespace App\Http\Requests\Api\V1\Admin\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'subject'       => ['required', 'string', 'max:300'],
            'description'   => ['nullable', 'string'],
            'status'        => ['sometimes', 'integer', 'between:2,5'],
            'priority'      => ['sometimes', 'integer', 'between:1,4'],
            'source'        => ['sometimes', 'integer'],
            'type'          => ['sometimes', 'nullable', 'string', 'max:80'],
            'requester_id'  => ['sometimes', 'nullable', 'integer'],
            'responder_id'  => ['sometimes', 'nullable', 'integer'],
            'group_id'      => ['sometimes', 'nullable', 'integer'],
            'company_id'    => ['sometimes', 'nullable', 'integer'],
            'product_id'    => ['sometimes', 'nullable', 'integer'],
            'tags'          => ['sometimes', 'array'],
            'tags.*'        => ['string', 'max:60'],
            'cc_emails'     => ['sometimes', 'array'],
            'to_emails'     => ['sometimes', 'array'],
            'due_by'        => ['sometimes', 'nullable', 'date'],
            'fr_due_by'     => ['sometimes', 'nullable', 'date'],
            'custom_fields' => ['sometimes', 'array'],
            'attachments'   => ['sometimes', 'array'],
            'attachments.*' => ['file', 'max:20480'],
        ];
    }
}
