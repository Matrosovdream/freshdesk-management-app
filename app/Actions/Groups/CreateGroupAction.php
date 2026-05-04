<?php

namespace App\Actions\Groups;

use App\Models\Group;
use App\Support\AuditWriter;

final class CreateGroupAction
{
    public function handle(array $data = []): array
    {
        $max = (int) Group::max('freshdesk_id');
        $payload = array_intersect_key($data, array_flip([
            'name', 'description', 'unassigned_for', 'business_hour_id', 'escalate_to',
            'agent_ids', 'auto_ticket_assign',
        ]));
        $payload['freshdesk_id']  = $max > 0 ? $max + 1 : 1_000_000;
        $payload['fd_created_at'] = now();
        $payload['fd_updated_at'] = now();

        $g = Group::create($payload);

        AuditWriter::log('group.created', 'Group', $g->id, [], $g->toArray());
        
        return $g->toArray();
    }
}
