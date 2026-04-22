<?php

namespace App\Repositories\Ticket;

use App\Models\Conversation;
use App\Repositories\AbstractRepo;

class ConversationRepo extends AbstractRepo
{
    public function __construct()
    {
        $this->model = new Conversation();
    }

    public function getByTicket(int $ticketId, $paginate = 50)
    {
        $query = $this->model
            ->where('ticket_id', $ticketId)
            ->orderBy('fd_created_at', 'asc');

        return $this->mapItems($query->paginate($paginate));
    }

    public function publicForCustomer(int $ticketId, $paginate = 50)
    {
        $query = $this->model
            ->where('ticket_id', $ticketId)
            ->where('private', false)
            ->orderBy('fd_created_at', 'asc');

        return $this->mapItems($query->paginate($paginate));
    }

    public function upsertFromFreshdesk(array $payload, int $ticketId): array
    {
        $item = $this->model->updateOrCreate(
            ['freshdesk_id' => $payload['id']],
            [
                'ticket_id'             => $ticketId,
                'freshdesk_ticket_id'   => $payload['ticket_id'] ?? null,
                'user_id'               => $payload['user_id'] ?? null,
                'body'                  => $payload['body'] ?? null,
                'body_text'             => $payload['body_text'] ?? null,
                'private'               => $payload['private'] ?? false,
                'incoming'              => $payload['incoming'] ?? false,
                'source'                => $payload['source'] ?? null,
                'from_email'            => $payload['from_email'] ?? null,
                'to_emails'             => $payload['to_emails'] ?? null,
                'cc_emails'             => $payload['cc_emails'] ?? null,
                'bcc_emails'            => $payload['bcc_emails'] ?? null,
                'attachments'           => $payload['attachments'] ?? null,
                'payload'               => $payload,
                'fd_created_at'         => $payload['created_at'] ?? null,
                'fd_updated_at'         => $payload['updated_at'] ?? null,
                'synced_at'             => now(),
            ],
        );

        return $this->mapItem($item->fresh());
    }

    public function mapItem($item)
    {
        if (empty($item)) {
            return null;
        }

        return [
            'id'            => $item->id,
            'freshdesk_id'  => $item->freshdesk_id,
            'ticket_id'     => $item->ticket_id,
            'user_id'       => $item->user_id,
            'body'          => $item->body,
            'body_text'     => $item->body_text,
            'private'       => (bool) $item->private,
            'incoming'      => (bool) $item->incoming,
            'from_email'    => $item->from_email,
            'attachments'   => $item->attachments,
            'fd_created_at' => $item->fd_created_at,
            'Model'         => $item,
        ];
    }
}
