<?php

namespace App\Actions\System\Users;

use App\Models\Role;
use App\Models\User;
use App\Support\AuditWriter;
use Illuminate\Support\Facades\Hash;

final class UpdateUserAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $u = User::findOrFail($id);
        $before = $u->only(['name', 'email', 'is_active']);

        $patch = array_intersect_key($data, array_flip(['name', 'email', 'is_active', 'phone', 'avatar']));
        if (!empty($data['password'])) $patch['password'] = Hash::make($data['password']);
        if (array_key_exists('pin', $data)) {
            $patch['pin'] = $data['pin'] === null || $data['pin'] === '' ? null : (string) $data['pin'];
        }
        $u->fill($patch)->save();

        if (array_key_exists('role_ids', $data)) {
            $roleIds = array_values(array_unique(array_map('intval', (array) $data['role_ids'])));
            $valid = Role::whereIn('id', $roleIds)->pluck('id')->all();
            $u->roles()->sync($valid);
        }

        if (array_key_exists('group_ids', $data)) {
            $u->managerGroups()->sync(array_map('intval', (array) $data['group_ids']));
        }

        AuditWriter::log('user.updated', 'User', $u->id, $before, $u->only(['name', 'email', 'is_active']));

        return (new GetUserAction)->handle(['id' => $u->id]);
    }
}
