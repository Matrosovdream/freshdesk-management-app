<?php

namespace App\Http\Requests\Api\V1\Admin\Agents;

use Illuminate\Foundation\Http\FormRequest;

class BulkCreateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['file' => ['required', 'file', 'mimes:csv,txt']];
    }
}
