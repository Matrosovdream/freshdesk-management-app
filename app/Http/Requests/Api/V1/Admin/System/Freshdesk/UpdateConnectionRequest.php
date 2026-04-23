<?php

namespace App\Http\Requests\Api\V1\Admin\System\Freshdesk;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConnectionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'domain'  => ['sometimes', 'string', 'regex:/^[a-z0-9-]+\.freshdesk\.com$/i'],
            'api_key' => ['sometimes', 'string', 'min:8'],
        ];
    }
}
