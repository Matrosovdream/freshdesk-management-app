<?php

namespace App\Mixins\Integrations\Freshdesk\Dto;

final class TicketPayload
{
    public function __construct(
        public string $subject,
        public string $description,
        public int $status = 2,
        public int $priority = 1,
        public int $source = 2,
        public ?int $requesterId = null,
        public ?string $email = null,
        public ?int $responderId = null,
        public ?int $groupId = null,
        public ?int $companyId = null,
        public ?int $productId = null,
        public ?string $type = null,
        public array $tags = [],
        public array $ccEmails = [],
        public ?string $dueBy = null,
        public ?string $frDueBy = null,
        public array $customFields = [],
        public array $attachments = [],
    ) {
        if ($requesterId === null && ($email === null || $email === '')) {
            throw new \InvalidArgumentException('requester_id or email is required');
        }
    }

    public function toArray(): array
    {
        return array_filter([
            'subject'        => $this->subject,
            'description'    => $this->description,
            'status'         => $this->status,
            'priority'       => $this->priority,
            'source'         => $this->source,
            'requester_id'   => $this->requesterId,
            'email'          => $this->email,
            'responder_id'   => $this->responderId,
            'group_id'       => $this->groupId,
            'company_id'     => $this->companyId,
            'product_id'     => $this->productId,
            'type'           => $this->type,
            'tags'           => $this->tags ?: null,
            'cc_emails'      => $this->ccEmails ?: null,
            'due_by'         => $this->dueBy,
            'fr_due_by'      => $this->frDueBy,
            'custom_fields'  => $this->customFields ?: null,
        ], fn ($v) => $v !== null);
    }
}
