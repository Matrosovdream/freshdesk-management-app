<?php

namespace App\Http\Requests\Api\V1\Admin\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class MergeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'primary_id'      => ['sometimes', 'integer'],
            'secondary_ids'   => ['required', 'array', 'min:1'],
            'secondary_ids.*' => ['integer'],
        ];
    }
}
