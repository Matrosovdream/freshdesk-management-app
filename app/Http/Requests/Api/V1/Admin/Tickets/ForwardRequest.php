<?php

namespace App\Http\Requests\Api\V1\Admin\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class ForwardRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'body'        => ['required', 'string'],
            'to_emails'   => ['required', 'array', 'min:1'],
            'to_emails.*' => ['email'],
            'cc_emails'   => ['sometimes', 'array'],
            'bcc_emails'  => ['sometimes', 'array'],
        ];
    }
}
