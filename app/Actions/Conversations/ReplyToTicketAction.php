<?php

namespace App\Actions\Conversations;

use App\Models\Conversation;
use App\Models\Ticket;
use App\Support\AuditWriter;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ReplyToTicketAction
{
    public function handle(array $data = []): array
    {
        $ticketId = (int) ($data['ticket_id'] ?? 0);

        $ticket = Ticket::find($ticketId);
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
            'source'              => 0,
            'from_email'          => $data['from_email'] ?? null,
            'to_emails'           => (array) ($data['to_emails'] ?? []),
            'cc_emails'           => (array) ($data['cc_emails'] ?? []),
            'bcc_emails'          => (array) ($data['bcc_emails'] ?? []),
            'fd_created_at'       => now(),
        ]);

        $ticket->fd_updated_at = now();
        $ticket->save();

        AuditWriter::log('conversation.reply', 'Ticket', $ticket->id, [], ['conversation_id' => $conv->id]);
        
        return $conv->toArray();
    }
}
