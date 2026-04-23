<?php

namespace App\Actions\Agents;

use App\Models\Agent;
use App\Support\AuditWriter;

final class CreateAgentAction
{
    public function handle(array $data = []): array
    {
        $max = (int) Agent::max('freshdesk_id');
        $payload = array_intersect_key($data, array_flip([
            'email', 'name', 'job_title', 'language', 'time_zone', 'available',
            'occasional', 'type', 'ticket_scope', 'signature',
            'group_ids', 'role_ids', 'skill_ids',
        ]));
        $payload['freshdesk_id']  = $max > 0 ? $max + 1 : 1_000_000;
        $payload['fd_created_at'] = now();
        $payload['fd_updated_at'] = now();

        $a = Agent::create($payload);
        AuditWriter::log('agent.created', 'Agent', $a->id, [], $a->toArray());
        return $a->toArray();
    }
}
