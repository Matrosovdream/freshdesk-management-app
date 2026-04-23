<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Support\AuditWriter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AssignTicketAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $ticket = Ticket::find($id);
        if (! $ticket) throw new NotFoundHttpException('Ticket not found.');

        $before = $ticket->toArray();
        $ticket->responder_id = $data['responder_id'] ?? null;
        $ticket->fd_updated_at = now();
        $ticket->save();

        AuditWriter::log('ticket.assigned', 'Ticket', $ticket->id, $before, $ticket->fresh()->toArray());
        return $ticket->fresh(['responder'])->toArray();
    }
}
