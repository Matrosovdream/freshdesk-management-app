<?php

namespace App\Actions\Groups;

use App\Models\Group;
use App\Support\AuditWriter;

final class UpdateGroupAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $g = Group::findOrFail($id);
        $before = $g->toArray();

        $patch = array_intersect_key($data, array_flip([
            'name', 'description', 'unassigned_for', 'business_hour_id', 'escalate_to',
            'agent_ids', 'auto_ticket_assign',
        ]));
        $g->fill($patch);
        $g->fd_updated_at = now();
        $g->save();

        AuditWriter::log('group.updated', 'Group', $g->id, $before, $g->fresh()->toArray());
        return $g->fresh()->toArray();
    }
}
