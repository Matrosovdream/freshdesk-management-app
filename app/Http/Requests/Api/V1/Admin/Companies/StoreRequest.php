<?php

namespace App\Http\Requests\Api\V1\Admin\Companies;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:160', 'unique:companies,name'],
            'description'  => ['sometimes', 'nullable', 'string'],
            'domains'      => ['sometimes', 'array'],
            'industry'     => ['sometimes', 'nullable', 'string'],
            'account_tier' => ['sometimes', 'nullable', 'string'],
            'health_score' => ['sometimes', 'nullable', 'string'],
            'renewal_date' => ['sometimes', 'nullable', 'date'],
            'note'         => ['sometimes', 'nullable', 'string'],
            'custom_fields'=> ['sometimes', 'array'],
        ];
    }
}
