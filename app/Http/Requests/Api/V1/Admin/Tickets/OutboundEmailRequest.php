<?php

namespace App\Http\Requests\Api\V1\Admin\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class OutboundEmailRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'subject'     => ['required', 'string', 'max:300'],
            'body'        => ['required', 'string'],
            'to_emails'   => ['required', 'array', 'min:1'],
            'to_emails.*' => ['email'],
            'cc_emails'   => ['sometimes', 'array'],
        ];
    }
}
