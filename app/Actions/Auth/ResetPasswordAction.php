<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final class ResetPasswordAction
{
    public function handle(array $data = []): array
    {
        $status = Password::broker()->reset(
            [
                'email'                 => $data['email'] ?? '',
                'password'              => $data['password'] ?? '',
                'password_confirmation' => $data['password_confirmation'] ?? '',
                'token'                 => $data['token'] ?? '',
            ],
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                Auth::guard('web')->login($user);
                request()->session()->regenerate();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return ['ok' => true];
    }
}
