<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Support\AuditWriter;
use App\Support\ManagerScope;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UpdateTicketAction
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
            if (isset($data['group_id']) && ! in_array((int) $data['group_id'], $scope, true)) {
                throw new AccessDeniedHttpException('You cannot move this ticket out of your scope.');
            }
        }

        $before = $ticket->toArray();
        $allowed = [
            'subject', 'description', 'description_text', 'status', 'priority', 'source', 'type',
            'requester_id', 'responder_id', 'group_id', 'company_id', 'product_id',
            'due_by', 'fr_due_by', 'spam', 'tags', 'custom_fields', 'cc_emails',
        ];
        $patch = array_intersect_key($data, array_flip($allowed));

        foreach (['tags', 'cc_emails', 'custom_fields'] as $k) {
            if (isset($patch[$k]) && is_string($patch[$k])) {
                $decoded = json_decode($patch[$k], true);
                if (is_array($decoded)) $patch[$k] = $decoded;
            }
        }

        $ticket->fill($patch);
        $ticket->fd_updated_at = now();
        $ticket->save();

        AuditWriter::log('ticket.updated', 'Ticket', $ticket->id, $before, $ticket->fresh()->toArray());

        return $ticket->fresh(['requester', 'responder', 'group', 'company'])->toArray();
    }
}
