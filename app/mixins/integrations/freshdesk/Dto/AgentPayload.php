<?php

namespace App\Mixins\Integrations\Freshdesk\Dto;

final class AgentPayload
{
    public function __construct(
        public string $email,
        public ?string $ticketScope = null,
        public bool $occasional = false,
        public ?string $signature = null,
        public array $skillIds = [],
        public array $groupIds = [],
        public array $roleIds = [],
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'email'         => $this->email,
            'ticket_scope'  => $this->ticketScope,
            'occasional'    => $this->occasional ?: null,
            'signature'     => $this->signature,
            'skill_ids'     => $this->skillIds ?: null,
            'group_ids'     => $this->groupIds ?: null,
            'role_ids'      => $this->roleIds ?: null,
        ], fn ($v) => $v !== null);
    }
}
