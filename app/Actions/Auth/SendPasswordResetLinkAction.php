<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Password;

final class SendPasswordResetLinkAction
{
    public function handle(array $data = []): array
    {
        $email = (string) ($data['email'] ?? '');
        if ($email === '') return ['ok' => true]; // don't leak

        // Uses the default "users" password broker.
        Password::broker()->sendResetLink(['email' => $email]);
        
        return ['ok' => true];
    }
}
