<?php

namespace App\Actions\TimeEntries;

use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Support\AuditWriter;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CreateTimeEntryAction
{
    public function handle(array $data = []): array
    {
        $ticketId = (int) ($data['ticket_id'] ?? $data['id'] ?? 0);
        $ticket = Ticket::find($ticketId);
        if (! $ticket) throw new NotFoundHttpException('Ticket not found.');

        $entry = TimeEntry::create([
            'freshdesk_id'        => random_int(PHP_INT_MAX - 1_000_000_000, PHP_INT_MAX),
            'ticket_id'           => $ticket->id,
            'freshdesk_ticket_id' => $ticket->freshdesk_id,
            'agent_id'            => $data['agent_id'] ?? null,
            'time_spent'          => $data['time_spent'] ?? '00:00',
            'note'                => $data['note'] ?? null,
            'billable'            => (bool) ($data['billable'] ?? false),
            'timer_running'       => (bool) ($data['timer_running'] ?? false),
            'executed_at'         => $data['executed_at'] ?? now(),
            'start_time'          => ($data['timer_running'] ?? false) ? now() : null,
            'fd_created_at'       => now(),
        ]);

        AuditWriter::log('time_entry.created', 'TimeEntry', $entry->id, [], $entry->toArray());
        
        return $entry->toArray();
    }
}
