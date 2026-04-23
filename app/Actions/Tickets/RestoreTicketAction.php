<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Support\AuditWriter;

final class RestoreTicketAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $ticket = Ticket::withTrashed()->findOrFail($id);
        $ticket->restore();
        AuditWriter::log('ticket.restored', 'Ticket', $ticket->id);
        return $ticket->fresh()->toArray();
    }
}
