<?php

namespace App\Mixins\Integrations\Freshdesk;

use App\Repositories\System\SettingRepo;
use Illuminate\Support\Facades\Cache;

class Config
{
    private const CACHE_TTL = 60;
    private const CACHE_KEY = 'freshdesk:config';

    public function __construct(private SettingRepo $settings) {}

    public function domain(): ?string
    {
        return $this->all()['domain'] ?? null;
    }

    public function apiKey(): ?string
    {
        return $this->all()['api_key'] ?? null;
    }

    public function webhookSecret(): ?string
    {
        return $this->all()['webhook_secret'] ?? null;
    }

    public function planLimitPerHour(): int
    {
        return (int) ($this->all()['plan_limit_per_hour'] ?? 3000);
    }

    public function baseUrl(): string
    {
        $domain = $this->domain();
        if (!$domain) {
            throw new \RuntimeException('Freshdesk domain is not configured.');
        }

        return rtrim("https://{$domain}", '/') . '/api/v2';
    }

    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return [
                'domain'              => $this->settings->get('freshdesk.domain'),
                'api_key'             => $this->settings->get('freshdesk.api_key'),
                'webhook_secret'      => $this->settings->get('freshdesk.webhook_secret'),
                'plan_limit_per_hour' => $this->settings->get('freshdesk.plan_limit_per_hour', 3000),
            ];
        });
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
