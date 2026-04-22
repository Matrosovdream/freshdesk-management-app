<?php

namespace App\Http\Requests\Api\V1\Portal\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required_without:pin', 'nullable', 'email'],
            'password' => ['required_without:pin', 'nullable', 'string'],
            'pin'      => ['required_without:email', 'nullable', 'string', 'digits:4'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }
}
