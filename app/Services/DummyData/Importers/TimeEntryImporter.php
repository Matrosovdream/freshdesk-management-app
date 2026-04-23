<?php

namespace App\Services\DummyData\Importers;

use App\Models\Agent;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Repositories\Ticket\TimeEntryRepo;
use App\Services\DummyData\DummyDataLoader;

class TimeEntryImporter
{
    public function __construct(
        private DummyDataLoader $loader,
        private TimeEntryRepo $timeEntryRepo,
    ) {}

    public function import(): int
    {
        $ticketMap = Ticket::query()->pluck('id', 'freshdesk_id')->all();
        $agentMap  = Agent::query()->pluck('id', 'freshdesk_id')->all();
        $count = 0;

        foreach ($this->loader->load('time_entries.json') as $payload) {
            $fdId       = $payload['id'] ?? null;
            $fdTicketId = $payload['ticket_id'] ?? null;
            $fdAgentId  = $payload['agent_id'] ?? null;

            if (!$fdId || !$fdTicketId) {
                continue;
            }

            $ticketId = $ticketMap[$fdTicketId] ?? null;
            if ($ticketId === null) {
                continue;
            }

            if (TimeEntry::query()->where('freshdesk_id', $fdId)->exists()) {
                continue;
            }

            $agentId = $fdAgentId ? ($agentMap[$fdAgentId] ?? null) : null;

            $this->timeEntryRepo->upsertFromFreshdesk($payload, (int) $ticketId, $agentId);
            $count++;
        }

        return $count;
    }
}
