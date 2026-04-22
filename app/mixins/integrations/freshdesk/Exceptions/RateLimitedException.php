<?php

namespace App\Mixins\Integrations\Freshdesk\Exceptions;

class RateLimitedException extends FreshdeskException
{
    public function __construct(
        string $message = 'Freshdesk rate limit exceeded.',
        int $code = 429,
        ?\Throwable $previous = null,
        public int $retryAfterSeconds = 0,
        array $context = [],
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
