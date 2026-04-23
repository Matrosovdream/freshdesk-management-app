<?php

namespace App\Actions\System\Managers;

use App\Models\Role;
use App\Models\User;
use App\Support\AuditWriter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class CreateManagerAction
{
    public function handle(array $data = []): array
    {
        $user = User::create([
            'email'     => $data['email'],
            'name'      => $data['name'] ?? explode('@', $data['email'])[0],
            'password'  => Hash::make($data['password'] ?? Str::random(20)),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'email_verified_at' => now(),
        ]);

        $role = Role::where('slug', 'manager')->first();
        if ($role) $user->roles()->syncWithoutDetaching([$role->id]);

        if (!empty($data['group_ids'])) {
            $user->managerGroups()->sync(array_map('intval', $data['group_ids']));
        }

        AuditWriter::log('manager.created', 'User', $user->id, [], ['email' => $user->email]);
        return $user->fresh(['roles', 'managerGroups'])->toArray();
    }
}
