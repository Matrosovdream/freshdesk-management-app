<?php

namespace App\Mixins\Integrations\Freshdesk\Dto;

final class GroupPayload
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?int $unassignedFor = null,
        public ?int $businessHourId = null,
        public ?int $escalateTo = null,
        public array $agentIds = [],
        public bool $autoTicketAssign = false,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'name'                => $this->name,
            'description'         => $this->description,
            'unassigned_for'      => $this->unassignedFor,
            'business_hour_id'    => $this->businessHourId,
            'escalate_to'         => $this->escalateTo,
            'agent_ids'           => $this->agentIds ?: null,
            'auto_ticket_assign'  => $this->autoTicketAssign ?: null,
        ], fn ($v) => $v !== null);
    }
}
