<?php

namespace App\Http\Requests\Api\V1\Admin\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return []; }
}
