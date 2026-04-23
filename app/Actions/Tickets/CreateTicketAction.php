<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Support\AuditWriter;
use Illuminate\Support\Facades\DB;

final class CreateTicketAction
{
    public function handle(array $data = []): array
    {
        $payload = $this->normalise($data);

        $ticket = DB::transaction(function () use ($payload) {
            $max = (int) Ticket::max('freshdesk_id');
            $payload['freshdesk_id']  = $max > 0 ? $max + 1 : 1_000_000;
            $payload['fd_created_at'] = now();
            $payload['fd_updated_at'] = now();

            $t = Ticket::create($payload);
            AuditWriter::log('ticket.created', 'Ticket', $t->id, [], $t->toArray());
            return $t;
        });

        return $ticket->fresh(['requester', 'responder', 'group', 'company'])->toArray();
    }

    private function normalise(array $data): array
    {
        foreach (['tags', 'cc_emails', 'to_emails', 'fwd_emails', 'reply_cc_emails', 'custom_fields'] as $k) {
            if (isset($data[$k]) && is_string($data[$k])) {
                $decoded = json_decode($data[$k], true);
                if (is_array($decoded)) $data[$k] = $decoded;
            }
        }

        return array_intersect_key($data, array_flip([
            'subject', 'description', 'description_text', 'status', 'priority', 'source', 'type',
            'requester_id', 'responder_id', 'group_id', 'company_id', 'product_id',
            'email_config_id', 'parent_id', 'due_by', 'fr_due_by',
            'to_emails', 'cc_emails', 'fwd_emails', 'reply_cc_emails', 'tags', 'custom_fields',
        ]));
    }
}
