<?php

namespace App\Repositories\Group;

use App\Models\Group;
use App\Repositories\AbstractRepo;

class GroupRepo extends AbstractRepo
{
    public function __construct()
    {
        $this->model = new Group();
    }

    public function getByFreshdeskId(int $fdId)
    {
        return $this->mapItem($this->model->where('freshdesk_id', $fdId)->first());
    }

    public function getByIds(array $ids)
    {
        return $this->mapItems($this->model->whereIn('id', $ids)->paginate(100));
    }

    public function upsertFromFreshdesk(array $payload): array
    {
        $item = $this->model->updateOrCreate(
            ['freshdesk_id' => $payload['id']],
            [
                'name'               => $payload['name'] ?? '',
                'description'        => $payload['description'] ?? null,
                'unassigned_for'     => $payload['unassigned_for'] ?? null,
                'business_hour_id'   => $payload['business_hour_id'] ?? null,
                'escalate_to'        => $payload['escalate_to'] ?? null,
                'agent_ids'          => $payload['agent_ids'] ?? null,
                'auto_ticket_assign' => $payload['auto_ticket_assign'] ?? false,
                'payload'            => $payload,
                'fd_created_at'      => $payload['created_at'] ?? null,
                'fd_updated_at'      => $payload['updated_at'] ?? null,
                'synced_at'          => now(),
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
            'id'                 => $item->id,
            'freshdesk_id'       => $item->freshdesk_id,
            'name'               => $item->name,
            'description'        => $item->description,
            'unassigned_for'     => $item->unassigned_for,
            'auto_ticket_assign' => (bool) $item->auto_ticket_assign,
            'agent_ids'          => $item->agent_ids,
            'fd_updated_at'      => $item->fd_updated_at,
            'Model'              => $item,
        ];
    }
}
