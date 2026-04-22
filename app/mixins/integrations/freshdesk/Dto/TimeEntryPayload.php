<?php

namespace App\Mixins\Integrations\Freshdesk\Dto;

final class TimeEntryPayload
{
    public function __construct(
        public string $timeSpent,
        public ?int $agentId = null,
        public ?string $note = null,
        public bool $billable = true,
        public ?string $executedAt = null,
        public bool $timerRunning = false,
    ) {
        if (!preg_match('/^\d{1,3}:\d{2}$/', $timeSpent)) {
            throw new \InvalidArgumentException('time_spent must be in HH:MM format');
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'time_spent'    => $this->timeSpent,
            'agent_id'      => $this->agentId,
            'note'          => $this->note,
            'billable'      => $this->billable,
            'executed_at'   => $this->executedAt,
            'timer_running' => $this->timerRunning ?: null,
        ], fn ($v) => $v !== null);
    }
}
