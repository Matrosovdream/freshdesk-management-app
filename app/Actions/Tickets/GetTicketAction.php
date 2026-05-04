<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Support\ManagerScope;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetTicketAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        
        $ticket = Ticket::with(['requester', 'responder', 'group', 'company'])->find($id);
        if (! $ticket) throw new NotFoundHttpException('Ticket not found.');

        if (ManagerScope::isManager()) {
            $scope = ManagerScope::groupIds();
            if (! in_array((int) $ticket->group_id, $scope, true)) {
                throw new AccessDeniedHttpException("This ticket belongs to a group you don't manage.");
            }
        }

        return $ticket->toArray();
    }
}
