<?php

namespace App\Mixins\Integrations\Freshdesk\Dto;

final class CompanyPayload
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public array $domains = [],
        public ?string $note = null,
        public ?int $healthScore = null,
        public ?string $accountTier = null,
        public ?string $renewalDate = null,
        public ?string $industry = null,
        public array $customFields = [],
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'name'          => $this->name,
            'description'   => $this->description,
            'domains'       => $this->domains ?: null,
            'note'          => $this->note,
            'health_score'  => $this->healthScore,
            'account_tier'  => $this->accountTier,
            'renewal_date'  => $this->renewalDate,
            'industry'      => $this->industry,
            'custom_fields' => $this->customFields ?: null,
        ], fn ($v) => $v !== null);
    }
}
