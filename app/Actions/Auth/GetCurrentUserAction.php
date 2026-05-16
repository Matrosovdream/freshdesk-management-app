<?php

namespace App\Actions\Auth;

use App\Services\UserSettingsService;
use Illuminate\Support\Facades\Auth;

final class GetCurrentUserAction
{
    public function __construct(private UserSettingsService $settings) {}

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
            'preferences'     => $this->settings->all($user),
            'roles'           => $user->roles->pluck('slug')->all(),
            'rights'          => method_exists($user, 'rights') ? $user->rights() : [],
            'assigned_groups' => $assignedGroups,
        ];
    }
}
