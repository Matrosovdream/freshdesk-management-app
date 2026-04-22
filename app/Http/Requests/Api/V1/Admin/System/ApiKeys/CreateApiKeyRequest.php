<?php

namespace App\Http\Requests\Api\V1\Admin\System\ApiKeys;

use Illuminate\Foundation\Http\FormRequest;

class CreateApiKeyRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return []; }
}
