<?php

namespace App\Services\DummyData\Importers;

use App\Models\Agent;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Ticket;
use App\Repositories\Ticket\TicketRepo;
use App\Services\DummyData\DummyDataLoader;

class TicketImporter
{
    public function __construct(
        private DummyDataLoader $loader,
        private TicketRepo $ticketRepo,
    ) {}

    public function import(): int
    {
        $count = 0;

        foreach ($this->loader->load('tickets.json') as $payload) {
            if (empty($payload['id'])) {
                continue;
            }

            if ($this->ticketRepo->getByFreshdeskId((int) $payload['id'])) {
                continue;
            }

            $this->ticketRepo->upsertFromFreshdesk($payload);
            $count++;
        }

        $this->linkLocalRelations();

        return $count;
    }

    private function linkLocalRelations(): void
    {
        $contactMap = Contact::query()->pluck('id', 'freshdesk_id')->all();
        $agentMap   = Agent::query()->pluck('id', 'freshdesk_id')->all();
        $groupMap   = Group::query()->pluck('id', 'freshdesk_id')->all();
        $companyMap = Company::query()->pluck('id', 'freshdesk_id')->all();

        Ticket::query()->get()->each(function (Ticket $ticket) use ($contactMap, $agentMap, $groupMap, $companyMap) {
            $updates = [];

            if ($ticket->requester_id === null && $ticket->freshdesk_requester_id) {
                $updates['requester_id'] = $contactMap[$ticket->freshdesk_requester_id] ?? null;
            }
            if ($ticket->responder_id === null && $ticket->freshdesk_responder_id) {
                $updates['responder_id'] = $agentMap[$ticket->freshdesk_responder_id] ?? null;
            }
            if ($ticket->group_id === null && $ticket->freshdesk_group_id) {
                $updates['group_id'] = $groupMap[$ticket->freshdesk_group_id] ?? null;
            }
            if ($ticket->company_id === null && $ticket->freshdesk_company_id) {
                $updates['company_id'] = $companyMap[$ticket->freshdesk_company_id] ?? null;
            }

            $updates = array_filter($updates, fn ($v) => $v !== null);

            if (!empty($updates)) {
                $ticket->forceFill($updates)->save();
            }
        });
    }
}
