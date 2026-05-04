<?php

namespace App\Actions\System\Managers;

use App\Models\User;
use App\Support\AuditWriter;

final class SetManagerScopeAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $u = User::findOrFail($id);
        $ids = array_map('intval', (array) ($data['group_ids'] ?? []));
        $u->managerGroups()->sync($ids);

        AuditWriter::log('manager.scope_updated', 'User', $u->id, [], ['group_ids' => $ids]);
        
        return ['id' => $u->id, 'group_ids' => $ids];
    }
}
