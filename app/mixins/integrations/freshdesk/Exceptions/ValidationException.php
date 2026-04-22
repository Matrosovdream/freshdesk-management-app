<?php

namespace App\Mixins\Integrations\Freshdesk\Exceptions;

class ValidationException extends FreshdeskException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        public array $errors = [],
        array $context = [],
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
