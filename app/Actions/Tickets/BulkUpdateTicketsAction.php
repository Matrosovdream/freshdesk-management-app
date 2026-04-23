<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Support\AuditWriter;
use App\Support\ManagerScope;

final class BulkUpdateTicketsAction
{
    public function handle(array $data = []): array
    {
        $ids = array_map('intval', (array) ($data['ids'] ?? []));
        $properties = (array) ($data['properties'] ?? []);
        if (empty($ids) || empty($properties)) return ['updated' => 0];

        $allowed = ['status', 'priority', 'responder_id', 'group_id', 'type', 'spam'];
        $patch = array_intersect_key($properties, array_flip($allowed));
        if (empty($patch)) return ['updated' => 0];

        $q = Ticket::whereIn('id', $ids);
        ManagerScope::applyToTickets($q);
        $count = $q->update($patch + ['fd_updated_at' => now()]);
        AuditWriter::log('tickets.bulk_updated', 'Ticket', null, [], ['ids' => $ids, 'patch' => $patch]);
        return ['updated' => $count];
    }
}
