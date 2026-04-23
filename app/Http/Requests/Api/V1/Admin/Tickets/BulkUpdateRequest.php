<?php

namespace App\Http\Requests\Api\V1\Admin\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'ids'        => ['required', 'array', 'min:1'],
            'ids.*'      => ['integer'],
            'properties' => ['required', 'array'],
        ];
    }
}
