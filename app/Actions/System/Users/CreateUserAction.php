<?php

namespace App\Actions\System\Users;

use App\Models\Role;
use App\Models\User;
use App\Support\AuditWriter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class CreateUserAction
{
    public function handle(array $data = []): array
    {
        $attrs = [
            'email'             => $data['email'],
            'name'              => $data['name'] ?? explode('@', $data['email'])[0],
            'password'          => Hash::make($data['password'] ?? Str::random(20)),
            'is_active'         => (bool) ($data['is_active'] ?? true),
            'email_verified_at' => now(),
        ];
        if (!empty($data['phone'])) $attrs['phone'] = $data['phone'];
        if (!empty($data['pin']))   $attrs['pin']   = $data['pin'];

        $user = User::create($attrs);

        if (!empty($data['role_ids'])) {
            $roleIds = array_values(array_unique(array_map('intval', (array) $data['role_ids'])));
            $valid = Role::whereIn('id', $roleIds)->pluck('id')->all();
            $user->roles()->sync($valid);
        }

        if (!empty($data['group_ids'])) {
            $user->managerGroups()->sync(array_map('intval', $data['group_ids']));
        }

        AuditWriter::log('user.created', 'User', $user->id, [], ['email' => $user->email]);

        return (new GetUserAction)->handle(['id' => $user->id]);
    }
}
