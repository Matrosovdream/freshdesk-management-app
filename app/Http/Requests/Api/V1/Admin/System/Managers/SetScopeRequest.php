<?php

namespace App\Http\Requests\Api\V1\Admin\System\Managers;

use Illuminate\Foundation\Http\FormRequest;

class SetScopeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'group_ids'   => ['required', 'array'],
            'group_ids.*' => ['integer'],
        ];
    }
}
