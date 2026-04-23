<?php

namespace App\Http\Requests\Api\V1\Admin\TimeEntries;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'time_spent'    => ['sometimes', 'string', 'max:10'],
            'note'          => ['sometimes', 'nullable', 'string'],
            'billable'      => ['sometimes', 'boolean'],
            'executed_at'   => ['sometimes', 'nullable', 'date'],
            'timer_running' => ['sometimes', 'boolean'],
        ];
    }
}
