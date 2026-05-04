<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Support\AuditWriter;
use App\Support\ManagerScope;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DeleteTicketAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        
        $ticket = Ticket::find($id);
        if (! $ticket) throw new NotFoundHttpException('Ticket not found.');

        if (ManagerScope::isManager()) {
            $scope = ManagerScope::groupIds();
            if (! in_array((int) $ticket->group_id, $scope, true)) {
                throw new AccessDeniedHttpException("This ticket belongs to a group you don't manage.");
            }
        }

        $ticket->delete();

        AuditWriter::log('ticket.deleted', 'Ticket', $ticket->id);

        return ['id' => $ticket->id, 'deleted' => true];
    }
}
