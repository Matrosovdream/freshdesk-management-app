<?php

namespace App\Actions\Portal\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class PortalLoginAction
{
    public function handle(array $data = []): array
    {
        $user = ! empty($data['pin'])
            ? $this->findByPin((string) $data['pin'])
            : $this->findByEmailPassword((string) ($data['email'] ?? ''), (string) ($data['password'] ?? ''));

        if (! $user) {
            throw ValidationException::withMessages([
                'credentials' => ['Invalid credentials.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'credentials' => ['This account is disabled.'],
            ]);
        }

        if (! $user->hasRole('customer')) {
            throw ValidationException::withMessages([
                'credentials' => ['Not authorised for the portal.'],
            ]);
        }

        Auth::guard('web')->login($user, (bool) ($data['remember'] ?? false));
        $user->forceFill(['last_login_at' => now()])->save();
        request()->session()->regenerate();

        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('slug')->all(),
        ];
    }

    private function findByEmailPassword(string $email, string $password): ?User
    {
        $user = User::where('email', $email)->first();
        return ($user && Hash::check($password, $user->password)) ? $user : null;
    }

    private function findByPin(string $pin): ?User
    {
        foreach (User::whereNotNull('pin')->get() as $user) {
            if (Hash::check($pin, $user->pin)) {
                return $user;
            }
        }
        return null;
    }
}
