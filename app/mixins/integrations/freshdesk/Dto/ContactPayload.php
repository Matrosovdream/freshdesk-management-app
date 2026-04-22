<?php

namespace App\Mixins\Integrations\Freshdesk\Dto;

final class ContactPayload
{
    public function __construct(
        public string $name,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $mobile = null,
        public ?string $twitterId = null,
        public ?string $uniqueExternalId = null,
        public ?int $companyId = null,
        public array $otherEmails = [],
        public array $otherCompanies = [],
        public ?string $jobTitle = null,
        public ?string $address = null,
        public ?string $language = null,
        public ?string $timeZone = null,
        public array $tags = [],
        public array $customFields = [],
        public bool $viewAllTickets = false,
    ) {
        if (!array_filter([$email, $phone, $mobile, $twitterId, $uniqueExternalId])) {
            throw new \InvalidArgumentException('At least one of email/phone/mobile/twitter_id/unique_external_id is required');
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'name'                 => $this->name,
            'email'                => $this->email,
            'phone'                => $this->phone,
            'mobile'               => $this->mobile,
            'twitter_id'           => $this->twitterId,
            'unique_external_id'   => $this->uniqueExternalId,
            'company_id'           => $this->companyId,
            'other_emails'         => $this->otherEmails ?: null,
            'other_companies'      => $this->otherCompanies ?: null,
            'job_title'            => $this->jobTitle,
            'address'              => $this->address,
            'language'             => $this->language,
            'time_zone'            => $this->timeZone,
            'tags'                 => $this->tags ?: null,
            'custom_fields'        => $this->customFields ?: null,
            'view_all_tickets'     => $this->viewAllTickets ?: null,
        ], fn ($v) => $v !== null);
    }
}
