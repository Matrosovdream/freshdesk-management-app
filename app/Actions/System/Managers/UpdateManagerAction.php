<?php

namespace App\Actions\System\Managers;

use App\Models\User;
use App\Support\AuditWriter;
use Illuminate\Support\Facades\Hash;

final class UpdateManagerAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $u = User::findOrFail($id);
        $before = $u->only(['name', 'email', 'is_active']);

        $patch = array_intersect_key($data, array_flip(['name', 'email', 'is_active', 'phone', 'avatar']));
        if (!empty($data['password'])) $patch['password'] = Hash::make($data['password']);
        $u->fill($patch)->save();

        if (array_key_exists('group_ids', $data)) {
            $u->managerGroups()->sync(array_map('intval', (array) $data['group_ids']));
        }

        AuditWriter::log('manager.updated', 'User', $u->id, $before, $u->only(['name', 'email', 'is_active']));
        return $u->fresh(['roles', 'managerGroups'])->toArray();
    }
}
