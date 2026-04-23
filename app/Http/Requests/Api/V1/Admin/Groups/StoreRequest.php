<?php

namespace App\Http\Requests\Api\V1\Admin\Groups;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:120'],
            'description'        => ['sometimes', 'nullable', 'string'],
            'unassigned_for'     => ['sometimes', 'nullable', 'string', 'max:10'],
            'business_hour_id'   => ['sometimes', 'nullable', 'integer'],
            'escalate_to'        => ['sometimes', 'nullable', 'integer'],
            'agent_ids'          => ['sometimes', 'array'],
            'auto_ticket_assign' => ['sometimes', 'boolean'],
        ];
    }
}
