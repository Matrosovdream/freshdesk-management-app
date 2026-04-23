<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Auth;

final class LogoutAction
{
    public function handle(array $data = []): array
    {
        Auth::guard('web')->logout();
        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
        return ['ok' => true];
    }
}
