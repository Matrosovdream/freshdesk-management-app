<?php

namespace App\Actions\Agents;

use App\Models\Agent;
use App\Support\AuditWriter;

final class UpdateAgentAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $a = Agent::findOrFail($id);
        $before = $a->toArray();

        $patch = array_intersect_key($data, array_flip([
            'email', 'name', 'job_title', 'language', 'time_zone', 'available',
            'occasional', 'type', 'ticket_scope', 'signature',
            'group_ids', 'role_ids', 'skill_ids',
        ]));
        $a->fill($patch);
        $a->fd_updated_at = now();
        $a->save();

        AuditWriter::log('agent.updated', 'Agent', $a->id, $before, $a->fresh()->toArray());
        
        return $a->fresh()->toArray();
    }
}
