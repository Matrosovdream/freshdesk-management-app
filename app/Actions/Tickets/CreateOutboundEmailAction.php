<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Support\AuditWriter;
use Illuminate\Support\Facades\DB;

final class CreateOutboundEmailAction
{
    public function handle(array $data = []): array
    {
        $ticket = DB::transaction(function () use ($data) {

            $max = (int) Ticket::max('freshdesk_id');

            return Ticket::create([
                'freshdesk_id'    => $max > 0 ? $max + 1 : 1_000_000,
                'subject'         => $data['subject'] ?? 'Outbound email',
                'description'     => $data['body'] ?? null,
                'description_text'=> strip_tags($data['body'] ?? ''),
                'status'          => 5, // closed by default for outbound
                'priority'        => (int) ($data['priority'] ?? 1),
                'source'          => 10, // outbound_email source
                'requester_id'    => $data['requester_id'] ?? null,
                'responder_id'    => $data['responder_id'] ?? null,
                'to_emails'       => (array) ($data['to_emails'] ?? []),
                'cc_emails'       => (array) ($data['cc_emails'] ?? []),
                'fd_created_at'   => now(),
                'fd_updated_at'   => now(),
            ]);
            
        });

        AuditWriter::log('ticket.outbound_email', 'Ticket', $ticket->id);

        return $ticket->toArray();
    }
}
