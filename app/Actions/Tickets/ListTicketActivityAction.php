<?php

namespace App\Actions\Tickets;

use App\Models\AuditLog;
use App\Models\Ticket;
use App\Support\ManagerScope;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ListTicketActivityAction
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

        $limit = min(200, (int) ($data['per_page'] ?? 100));
        if ($limit <= 0) $limit = 100;

        return AuditLog::with('user')
            ->where('target_type', 'Ticket')
            ->where('target_id', $id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->all();
    }
}
