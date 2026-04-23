<?php

namespace App\Actions\TimeEntries;

use App\Models\TimeEntry;

final class ListTimeEntriesAction
{
    public function handle(array $data = []): array
    {
        $ticketId = (int) ($data['ticket_id'] ?? $data['id'] ?? 0);
        $q = TimeEntry::query()->with('agent');
        if ($ticketId) $q->where('ticket_id', $ticketId);
        if (!empty($data['agent_id'])) $q->where('agent_id', (int) $data['agent_id']);
        return $q->orderByDesc('executed_at')->limit(500)->get()->toArray();
    }
}
