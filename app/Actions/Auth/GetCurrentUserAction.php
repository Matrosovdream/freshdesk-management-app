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

        $assignedGroups = [];
        if (method_exists($user, 'managerGroups')) {
            $assignedGroups = $user->managerGroups()->get(['groups.id', 'groups.name'])->toArray();
        }

        return [
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'phone'           => $user->phone,
            'avatar'          => $user->avatar,
            'roles'           => $user->roles->pluck('slug')->all(),
            'rights'          => method_exists($user, 'rights') ? $user->rights() : [],
            'assigned_groups' => $assignedGroups,
        ];
    }
}
