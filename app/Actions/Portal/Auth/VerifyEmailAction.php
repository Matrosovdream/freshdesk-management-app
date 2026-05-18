<?php

namespace App\Actions\Portal\Auth;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Str;

final class VerifyEmailAction
{
    public function __construct(private NotificationService $notifications) {}

    public function handle(array $data = []): array
    {
        $email = (string) ($data['email'] ?? '');
        if ($email === '') {
            return ['ok' => true];
        }

        $user = User::where('email', $email)->first();
        if (! $user || $user->email_verified_at) {
            return ['ok' => true];
        }

        $token = Str::random(40);
        $this->notifications->sendEmailVerification($user, $token);

        return ['ok' => true, 'token' => $token];
    }
}
