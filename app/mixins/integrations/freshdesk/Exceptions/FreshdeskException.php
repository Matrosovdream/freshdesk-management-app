<?php

namespace App\Mixins\Integrations\Freshdesk\Exceptions;

class FreshdeskException extends \RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        public array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
    }
}
