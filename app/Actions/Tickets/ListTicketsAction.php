<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Support\ApiQuery;
use App\Support\ManagerScope;

final class ListTicketsAction
{
    public function handle(array $data = []): array
    {
        $q = Ticket::query()->with(['requester', 'responder', 'group', 'company']);

        ManagerScope::applyToTickets($q);

        if (!empty($data['status']))   $q->whereIn('status',   (array) $data['status']);
        if (!empty($data['priority'])) $q->whereIn('priority', (array) $data['priority']);
        if (!empty($data['responder_id'])) $q->where('responder_id', (int) $data['responder_id']);
        if (!empty($data['group_id']))     $q->where('group_id',     (int) $data['group_id']);
        if (!empty($data['company_id']))   $q->where('company_id',   (int) $data['company_id']);
        if (!empty($data['requester_id'])) $q->where('requester_id', (int) $data['requester_id']);
        if (!empty($data['tag'])) {
            foreach ((array) $data['tag'] as $t) $q->whereJsonContains('tags', $t);
        }
        if (!empty($data['created_from'])) $q->where('fd_created_at', '>=', $data['created_from']);
        if (!empty($data['created_to']))   $q->where('fd_created_at', '<=', $data['created_to']);
        if (!empty($data['updated_since'])) $q->where('fd_updated_at', '>=', $data['updated_since']);

        ApiQuery::applySearch($q, $data['search'] ?? null, ['subject', 'description_text']);
        ApiQuery::applyOrderBy($q, $data['sort'] ?? null, ['fd_updated_at', 'fd_created_at', 'priority', 'status', 'due_by'], 'fd_updated_at');

        return ApiQuery::page($q, $data);
    }
}
