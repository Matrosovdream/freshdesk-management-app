<?php

namespace App\Http\Requests\Api\V1\Admin\System\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'updates'         => ['required', 'array', 'min:1'],
            'updates.*.key'   => ['required', 'string'],
            'updates.*.value' => ['present'],
        ];
    }
}
