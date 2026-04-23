<?php

namespace App\Http\Requests\Api\V1\Admin\Conversations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'body'    => ['sometimes', 'string'],
            'private' => ['sometimes', 'boolean'],
        ];
    }
}
