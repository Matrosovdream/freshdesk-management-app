<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

class UniqueUserPin implements ValidationRule
{
    public function __construct(private ?int $ignoreUserId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $pin = (string) $value;

        $query = User::query()->whereNotNull('pin');
        if ($this->ignoreUserId !== null) {
            $query->where('id', '!=', $this->ignoreUserId);
        }

        foreach ($query->get(['id', 'pin']) as $user) {
            if (Hash::check($pin, $user->pin)) {
                $fail('This PIN is already in use. Please choose another.');
                return;
            }
        }
    }
}
