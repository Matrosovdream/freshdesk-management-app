<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Auth;

final class GetCurrentUserAction
{
    public function handle(array $data = []): array
    {
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        return [
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'roles'  => $user->roles->pluck('slug')->all(),
            'rights' => method_exists($user, 'rights') ? $user->rights() : [],
        ];
    }
}
