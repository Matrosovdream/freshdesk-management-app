<?php

namespace App\Mixins\Integrations\Freshdesk\Dto;

final class ConversationPayload
{
    public function __construct(
        public string $body,
        public ?int $userId = null,
        public ?int $emailConfigId = null,
        public array $fromEmail = [],
        public array $toEmails = [],
        public array $ccEmails = [],
        public array $bccEmails = [],
        public array $notifyEmails = [],
        public bool $private = false,
        public array $attachments = [],
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'body'            => $this->body,
            'user_id'         => $this->userId,
            'email_config_id' => $this->emailConfigId,
            'from_email'      => $this->fromEmail ?: null,
            'to_emails'       => $this->toEmails ?: null,
            'cc_emails'       => $this->ccEmails ?: null,
            'bcc_emails'      => $this->bccEmails ?: null,
            'notify_emails'   => $this->notifyEmails ?: null,
            'private'         => $this->private ?: null,
        ], fn ($v) => $v !== null);
    }
}
