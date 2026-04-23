<?php

namespace App\Http\Requests\Api\V1\Admin\TimeEntries;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'time_spent'    => ['required', 'string', 'max:10'],
            'note'          => ['sometimes', 'nullable', 'string'],
            'billable'      => ['sometimes', 'boolean'],
            'agent_id'      => ['sometimes', 'nullable', 'integer'],
            'executed_at'   => ['sometimes', 'nullable', 'date'],
            'timer_running' => ['sometimes', 'boolean'],
            'ticket_id'     => ['sometimes', 'integer'],
        ];
    }
}
