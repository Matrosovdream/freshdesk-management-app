<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Support\AuditWriter;
use App\Support\ManagerScope;

final class BulkDeleteTicketsAction
{
    public function handle(array $data = []): array
    {
        $ids = array_map('intval', (array) ($data['ids'] ?? []));
        
        if (empty($ids)) return ['deleted' => 0];

        $q = Ticket::whereIn('id', $ids);
        ManagerScope::applyToTickets($q);

        $count = $q->delete();
        AuditWriter::log('tickets.bulk_deleted', 'Ticket', null, [], ['ids' => $ids]);

        return ['deleted' => $count];
    }
}
