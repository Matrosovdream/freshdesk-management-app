<?php

namespace App\Actions\Tickets;

use App\Models\Conversation;
use App\Models\Ticket;
use App\Support\AuditWriter;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ForwardTicketAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $ticket = Ticket::find($id);
        if (! $ticket) throw new NotFoundHttpException('Ticket not found.');

        $conv = Conversation::create([
            'freshdesk_id'        => random_int(PHP_INT_MAX - 1_000_000_000, PHP_INT_MAX),
            'ticket_id'           => $ticket->id,
            'freshdesk_ticket_id' => $ticket->freshdesk_id,
            'user_id'             => Auth::id(),
            'body'                => $data['body'] ?? '',
            'body_text'           => strip_tags($data['body'] ?? ''),
            'private'             => false,
            'incoming'            => false,
            'source'              => 8,
            'to_emails'           => $data['to_emails'] ?? [],
            'cc_emails'           => $data['cc_emails'] ?? [],
            'bcc_emails'          => $data['bcc_emails'] ?? [],
        ]);

        AuditWriter::log('ticket.forwarded', 'Ticket', $ticket->id, [], ['conversation_id' => $conv->id]);
        return $conv->toArray();
    }
}
