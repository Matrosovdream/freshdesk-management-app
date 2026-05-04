<?php

namespace App\Actions\System\Managers;

use App\Models\User;
use App\Support\AuditWriter;

final class DeleteManagerAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        
        $u = User::findOrFail($id);
        $u->delete();

        AuditWriter::log('manager.deleted', 'User', $id);

        return ['id' => $id, 'deleted' => true];
    }
}
