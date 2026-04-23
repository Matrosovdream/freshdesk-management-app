<?php

namespace App\Http\Requests\Api\V1\Admin\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class AssignRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['responder_id' => ['nullable', 'integer']];
    }
}
