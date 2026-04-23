<?php

namespace App\Http\Requests\Api\V1\Admin\System\ApiKeys;

use Illuminate\Foundation\Http\FormRequest;

class CreateApiKeyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:120'],
            'scopes'     => ['required', 'array', 'min:1'],
            'scopes.*'   => ['string', 'max:80'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
