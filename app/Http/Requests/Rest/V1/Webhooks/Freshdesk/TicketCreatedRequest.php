<?php

namespace App\Http\Requests\Rest\V1\Webhooks\Freshdesk;

use Illuminate\Foundation\Http\FormRequest;

class TicketCreatedRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return []; }
}
