<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Password;

final class SendPasswordResetLinkAction
{
    public function __construct(private NotificationService $notifications) {}

    public function handle(array $data = []): array
    {
        $email = (string) ($data['email'] ?? '');
        if ($email === '') {
            return ['ok' => true];
        }

        $user = User::where('email', $email)->first();
        if (! $user) {
            return ['ok' => true];
        }

        $token = Password::broker()->createToken($user);
        $this->notifications->sendPasswordReset($user, $token);

        return ['ok' => true];
    }
}
