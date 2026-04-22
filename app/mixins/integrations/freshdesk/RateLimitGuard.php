<?php

namespace App\Mixins\Integrations\Freshdesk;

use App\Mixins\Integrations\Freshdesk\Exceptions\RateLimitedException;
use Illuminate\Support\Facades\Redis;

class RateLimitGuard
{
    private const BUCKET_KEY   = 'fd:ratelimit:bucket';
    private const LAST_KEY     = 'fd:ratelimit:last';
    private const UPDATED_KEY  = 'fd:ratelimit:updated_at';

    public function __construct(private Config $config) {}

    public function acquire(bool $interactive = true, int $maxWaitMs = 2000): void
    {
        $state = $this->refill();

        if ($state['tokens'] >= 1) {
            $this->writeBucket($state['tokens'] - 1, $state['updated_at']);
            return;
        }

        $waitMs = (int) ceil((1 - $state['tokens']) * 1000 / max($this->refillRatePerSecond(), 0.001));

        if (!$interactive || $waitMs > $maxWaitMs) {
            throw new RateLimitedException(
                'Local rate-limit bucket exhausted.',
                429,
                null,
                (int) ceil($waitMs / 1000),
            );
        }

        usleep($waitMs * 1000);
        $this->writeBucket(0, microtime(true));
    }

    public function reportRemaining(?int $remaining): void
    {
        if ($remaining === null) {
            return;
        }

        Redis::set(self::LAST_KEY, $remaining);
        Redis::set(self::UPDATED_KEY, (string) time());
        $this->writeBucket((float) $remaining, microtime(true));
    }

    public function lastRemaining(): ?array
    {
        $value = Redis::get(self::LAST_KEY);
        if ($value === null) {
            return null;
        }

        return [
            'remaining'  => (int) $value,
            'plan_limit' => $this->config->planLimitPerHour(),
            'updated_at' => (int) (Redis::get(self::UPDATED_KEY) ?? 0),
        ];
    }

    private function refill(): array
    {
        $raw     = Redis::get(self::BUCKET_KEY);
        $now     = microtime(true);
        $limit   = $this->config->planLimitPerHour();
        $refill  = $this->refillRatePerSecond();

        if (!$raw) {
            return ['tokens' => (float) $limit, 'updated_at' => $now];
        }

        [$tokens, $updatedAt] = array_pad(explode(':', $raw), 2, null);
        $tokens    = (float) $tokens;
        $updatedAt = (float) ($updatedAt ?? $now);
        $delta     = max(0.0, $now - $updatedAt);
        $tokens    = min((float) $limit, $tokens + $delta * $refill);

        return ['tokens' => $tokens, 'updated_at' => $now];
    }

    private function writeBucket(float $tokens, float $updatedAt): void
    {
        Redis::set(self::BUCKET_KEY, $tokens . ':' . $updatedAt);
    }

    private function refillRatePerSecond(): float
    {
        return $this->config->planLimitPerHour() / 3600.0;
    }
}
