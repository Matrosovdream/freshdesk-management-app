<?php

namespace App\Actions\Portal\Requests;

use App\Models\Contact;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ShowPortalRequestAction
{
    public function handle(array $data = []): array
    {
        $user = Auth::user();
        if (! $user || ! $user->freshdesk_contact_id) {
            throw new AccessDeniedHttpException();
        }

        $contact = Contact::query()
            ->where('freshdesk_id', $user->freshdesk_contact_id)
            ->first();

        if (! $contact) {
            throw new AccessDeniedHttpException();
        }

        $ticket = Ticket::query()
            ->with(['responder:id,name,email,freshdesk_id', 'conversations'])
            ->find((int) ($data['id'] ?? 0));

        if (! $ticket) {
            throw new NotFoundHttpException();
        }

        $allowed = $ticket->requester_id === $contact->id
            || $contact->company_id !== null && $ticket->company_id === $contact->company_id;

        if (! $allowed) {
            throw new AccessDeniedHttpException();
        }

        return [
            'id'               => $ticket->id,
            'subject'          => $ticket->subject,
            'status'           => $this->statusLabel((int) $ticket->status),
            'description'      => $ticket->description,
            'description_text' => $ticket->description_text,
            'created_at'       => ($ticket->fd_created_at ?? $ticket->created_at)?->toIso8601String(),
            'updated_at'       => ($ticket->fd_updated_at ?? $ticket->updated_at)?->toIso8601String(),
            'assigned_agent'   => $ticket->responder ? [
                'id'         => $ticket->responder->id,
                'name'       => $ticket->responder->name,
                'avatar_url' => null,
            ] : null,
            'conversations'    => $ticket->conversations->map(fn ($c) => [
                'id'           => $c->id,
                'body'         => $c->body_text ?? null,
                'body_html'    => $c->body ?? null,
                'from_customer' => (bool) ($c->incoming ?? false),
                'created_at'   => ($c->fd_created_at ?? $c->created_at)?->toIso8601String(),
                'author'       => null,
                'attachments'  => [],
            ])->all(),
            'rating'           => null,
        ];
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            2 => 'open',
            3 => 'pending',
            4 => 'resolved',
            5 => 'closed',
            default => 'open',
        };
    }
}
