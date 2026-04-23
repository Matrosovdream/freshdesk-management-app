<?php

namespace App\Http\Requests\Api\V1\Admin\Conversations;

use Illuminate\Foundation\Http\FormRequest;

class ReplyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'body'       => ['required', 'string'],
            'from_email' => ['sometimes', 'nullable', 'email'],
            'to_emails'  => ['sometimes', 'array'],
            'cc_emails'  => ['sometimes', 'array'],
            'bcc_emails' => ['sometimes', 'array'],
        ];
    }
}
