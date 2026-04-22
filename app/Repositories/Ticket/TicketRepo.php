<?php

namespace App\Repositories\Ticket;

use App\Models\Ticket;
use App\Repositories\AbstractRepo;

class TicketRepo extends AbstractRepo
{
    protected $withRelations = ['requester', 'responder', 'group', 'company'];

    public function __construct()
    {
        $this->model = new Ticket();
    }

    public function getByFreshdeskId(int $fdId)
    {
        $item = $this->model
            ->where('freshdesk_id', $fdId)
            ->with($this->withRelations)
            ->first();

        return $this->mapItem($item);
    }

    public function scopedToGroups(array $groupIds, array $filter = [], $paginate = 30)
    {
        $query = $this->model
            ->with($this->withRelations)
            ->whereIn('group_id', $groupIds);

        $query = $this->applyFilter($query, $filter);
        $query = $this->applySorting($query, []);

        return $this->mapItems($query->paginate($paginate));
    }

    public function overdue($paginate = 30)
    {
        $query = $this->model
            ->with($this->withRelations)
            ->where('due_by', '<', now())
            ->whereIn('status', [2, 3]);

        return $this->mapItems($query->paginate($paginate));
    }

    public function unassigned($paginate = 30)
    {
        $query = $this->model
            ->with($this->withRelations)
            ->whereNull('responder_id')
            ->whereIn('status', [2, 3]);

        return $this->mapItems($query->paginate($paginate));
    }

    public function pendingCustomerReply($paginate = 30)
    {
        $query = $this->model
            ->with($this->withRelations)
            ->where('status', 3);

        return $this->mapItems($query->paginate($paginate));
    }

    public function upsertFromFreshdesk(array $payload): array
    {
        $item = $this->model->updateOrCreate(
            ['freshdesk_id' => $payload['id']],
            [
                'subject'                 => $payload['subject'] ?? '',
                'description'             => $payload['description'] ?? null,
                'description_text'        => $payload['description_text'] ?? null,
                'status'                  => $payload['status'] ?? 2,
                'priority'                => $payload['priority'] ?? 1,
                'source'                  => $payload['source'] ?? null,
                'type'                    => $payload['type'] ?? null,
                'freshdesk_requester_id'  => $payload['requester_id'] ?? null,
                'freshdesk_responder_id'  => $payload['responder_id'] ?? null,
                'freshdesk_group_id'      => $payload['group_id'] ?? null,
                'freshdesk_company_id'    => $payload['company_id'] ?? null,
                'product_id'              => $payload['product_id'] ?? null,
                'email_config_id'         => $payload['email_config_id'] ?? null,
                'parent_id'               => $payload['parent_id'] ?? null,
                'spam'                    => $payload['spam'] ?? false,
                'is_escalated'            => $payload['is_escalated'] ?? false,
                'fr_escalated'            => $payload['fr_escalated'] ?? false,
                'due_by'                  => $payload['due_by'] ?? null,
                'fr_due_by'               => $payload['fr_due_by'] ?? null,
                'to_emails'               => $payload['to_emails'] ?? null,
                'cc_emails'               => $payload['cc_emails'] ?? null,
                'fwd_emails'              => $payload['fwd_emails'] ?? null,
                'reply_cc_emails'         => $payload['reply_cc_emails'] ?? null,
                'tags'                    => $payload['tags'] ?? null,
                'custom_fields'           => $payload['custom_fields'] ?? null,
                'payload'                 => $payload,
                'fd_created_at'           => $payload['created_at'] ?? null,
                'fd_updated_at'           => $payload['updated_at'] ?? null,
                'synced_at'               => now(),
            ],
        );

        return $this->mapItem($item->fresh($this->withRelations));
    }

    public function mapItem($item)
    {
        if (empty($item)) {
            return null;
        }

        return [
            'id'                     => $item->id,
            'freshdesk_id'           => $item->freshdesk_id,
            'subject'                => $item->subject,
            'status'                 => $item->status,
            'priority'               => $item->priority,
            'source'                 => $item->source,
            'type'                   => $item->type,
            'requester_id'           => $item->requester_id,
            'responder_id'           => $item->responder_id,
            'group_id'               => $item->group_id,
            'company_id'             => $item->company_id,
            'due_by'                 => $item->due_by,
            'fr_due_by'              => $item->fr_due_by,
            'tags'                   => $item->tags,
            'fd_created_at'          => $item->fd_created_at,
            'fd_updated_at'          => $item->fd_updated_at,
            'Model'                  => $item,
        ];
    }
}
