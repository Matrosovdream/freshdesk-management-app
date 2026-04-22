<?php

namespace App\Repositories\Ticket;

use App\Models\TimeEntry;
use App\Repositories\AbstractRepo;

class TimeEntryRepo extends AbstractRepo
{
    public function __construct()
    {
        $this->model = new TimeEntry();
    }

    public function getByTicket(int $ticketId, $paginate = 50)
    {
        return $this->getAll(['ticket_id' => $ticketId], $paginate);
    }

    public function getByAgent(int $agentId, $paginate = 50)
    {
        return $this->getAll(['agent_id' => $agentId], $paginate);
    }

    public function upsertFromFreshdesk(array $payload, int $ticketId, ?int $agentId = null): array
    {
        $item = $this->model->updateOrCreate(
            ['freshdesk_id' => $payload['id']],
            [
                'ticket_id'           => $ticketId,
                'freshdesk_ticket_id' => $payload['ticket_id'] ?? null,
                'agent_id'            => $agentId,
                'freshdesk_agent_id'  => $payload['agent_id'] ?? null,
                'time_spent'          => $payload['time_spent'] ?? null,
                'note'                => $payload['note'] ?? null,
                'billable'            => $payload['billable'] ?? false,
                'timer_running'       => $payload['timer_running'] ?? false,
                'executed_at'         => $payload['executed_at'] ?? null,
                'start_time'          => $payload['start_time'] ?? null,
                'payload'             => $payload,
                'fd_created_at'       => $payload['created_at'] ?? null,
                'fd_updated_at'       => $payload['updated_at'] ?? null,
                'synced_at'           => now(),
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
            'id'           => $item->id,
            'freshdesk_id' => $item->freshdesk_id,
            'ticket_id'    => $item->ticket_id,
            'agent_id'     => $item->agent_id,
            'time_spent'   => $item->time_spent,
            'note'         => $item->note,
            'billable'     => (bool) $item->billable,
            'executed_at'  => $item->executed_at,
            'Model'        => $item,
        ];
    }
}
