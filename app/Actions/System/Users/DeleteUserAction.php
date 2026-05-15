<?php

namespace App\Actions\System\Users;

use App\Models\User;
use App\Support\AuditWriter;

final class DeleteUserAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);

        $u = User::findOrFail($id);
        $u->delete();

        AuditWriter::log('user.deleted', 'User', $id);

        return ['id' => $id, 'deleted' => true];
    }
}
