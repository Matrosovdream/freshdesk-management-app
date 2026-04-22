<?php

namespace App\Mixins\Integrations\Freshdesk;

class RetryPolicy
{
    private const MAX_ATTEMPTS = 3;
    private const BACKOFF_MS   = [250, 500, 1000];

    public function maxAttempts(): int
    {
        return self::MAX_ATTEMPTS;
    }

    public function shouldRetry(int $attempt, ?int $status, ?\Throwable $transportError): bool
    {
        if ($attempt >= self::MAX_ATTEMPTS) {
            return false;
        }

        if ($transportError !== null) {
            return true;
        }

        if ($status === 429) {
            return true;
        }

        if ($status !== null && $status >= 500 && $status < 600) {
            return true;
        }

        return false;
    }

    public function waitMs(int $attempt, ?int $status, ?int $retryAfterSeconds): int
    {
        if ($status === 429 && $retryAfterSeconds !== null && $retryAfterSeconds > 0) {
            return $retryAfterSeconds * 1000;
        }

        $index = max(0, min($attempt - 1, count(self::BACKOFF_MS) - 1));
        return self::BACKOFF_MS[$index];
    }

    public function sleep(int $ms): void
    {
        usleep($ms * 1000);
    }
}
