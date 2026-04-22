<?php

namespace App\Repositories\People;

use App\Models\Agent;
use App\Repositories\AbstractRepo;

class AgentRepo extends AbstractRepo
{
    public function __construct()
    {
        $this->model = new Agent();
    }

    public function getByFreshdeskId(int $fdId)
    {
        return $this->mapItem($this->model->where('freshdesk_id', $fdId)->first());
    }

    public function getByEmail(string $email)
    {
        return $this->mapItem($this->model->where('email', $email)->first());
    }

    public function upsertFromFreshdesk(array $payload): array
    {
        $contact = $payload['contact'] ?? [];

        $item = $this->model->updateOrCreate(
            ['freshdesk_id' => $payload['id']],
            [
                'email'         => $contact['email'] ?? null,
                'name'          => $contact['name'] ?? null,
                'job_title'     => $contact['job_title'] ?? null,
                'language'      => $contact['language'] ?? null,
                'time_zone'     => $contact['time_zone'] ?? null,
                'available'     => $payload['available'] ?? false,
                'occasional'    => $payload['occasional'] ?? false,
                'type'          => $payload['type'] ?? null,
                'ticket_scope'  => $payload['ticket_scope'] ?? null,
                'signature'     => $payload['signature'] ?? null,
                'group_ids'     => $payload['group_ids'] ?? null,
                'role_ids'      => $payload['role_ids'] ?? null,
                'skill_ids'     => $payload['skill_ids'] ?? null,
                'payload'       => $payload,
                'fd_created_at' => $payload['created_at'] ?? null,
                'fd_updated_at' => $payload['updated_at'] ?? null,
                'synced_at'     => now(),
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
            'email'         => $item->email,
            'name'          => $item->name,
            'available'     => (bool) $item->available,
            'type'          => $item->type,
            'ticket_scope'  => $item->ticket_scope,
            'group_ids'     => $item->group_ids,
            'role_ids'      => $item->role_ids,
            'fd_updated_at' => $item->fd_updated_at,
            'Model'         => $item,
        ];
    }
}
