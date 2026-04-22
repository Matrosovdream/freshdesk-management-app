<?php

namespace App\Http\Requests\Api\V1\Portal\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return []; }
}
