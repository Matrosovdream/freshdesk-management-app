<?php

namespace App\Actions\Groups;

use App\Models\Group;
use App\Support\AuditWriter;

final class DeleteGroupAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $g = Group::findOrFail($id);
        $g->delete();
        AuditWriter::log('group.deleted', 'Group', $id);
        return ['id' => $id, 'deleted' => true];
    }
}
