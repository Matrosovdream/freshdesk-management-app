<?php

namespace App\Actions\System\Freshdesk;

use App\Models\Setting;
use App\Support\AuditWriter;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

final class TestConnectionAction
{
    public function handle(array $data = []): array
    {
        $domain = Setting::where('key', 'freshdesk.domain')->value('value');
        $enc    = Setting::where('key', 'freshdesk.api_key')->value('value');

        if (! $domain || ! $enc) {
            return ['ok' => false, 'message' => 'Domain or API key not configured.'];
        }

        try {
            $apiKey = Crypt::decryptString($enc);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Stored API key is unreadable — re-save the key.'];
        }

        try {
            $resp = Http::withBasicAuth($apiKey, 'X')
                ->acceptJson()
                ->timeout(10)
                ->get("https://{$domain}/api/v2/agents/me");

            $ok = $resp->ok();
            $remaining = (int) ($resp->header('X-Ratelimit-Remaining') ?? 0);
            $total     = (int) ($resp->header('X-Ratelimit-Total') ?? 0);

            Setting::updateOrCreate(['key' => 'freshdesk.test_ok'], ['value' => $ok ? '1' : '0', 'type' => 'boolean', 'group' => 'freshdesk']);
            Setting::updateOrCreate(['key' => 'freshdesk.rate_limit_remaining'], ['value' => (string) $remaining, 'type' => 'integer', 'group' => 'freshdesk']);
            if ($total) Setting::updateOrCreate(['key' => 'freshdesk.rate_limit_total'], ['value' => (string) $total, 'type' => 'integer', 'group' => 'freshdesk']);
            Setting::updateOrCreate(['key' => 'freshdesk.checked_at'], ['value' => now()->toIso8601String(), 'type' => 'string', 'group' => 'freshdesk']);

            AuditWriter::log('freshdesk.connection_tested', null, null, [], ['ok' => $ok, 'remaining' => $remaining]);

            if (! $ok) {
                return ['ok' => false, 'message' => "Freshdesk returned HTTP {$resp->status()}."];
            }

            $agent = $resp->json();
            return [
                'ok'        => true,
                'message'   => sprintf('Connected as %s. Plan limit: %s/hr.', $agent['contact']['name'] ?? $agent['name'] ?? 'agent', $total ?: '—'),
                'agent'     => ['id' => $agent['id'] ?? null, 'name' => $agent['contact']['name'] ?? $agent['name'] ?? null],
                'remaining' => $remaining,
                'total'     => $total,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Network error: '.$e->getMessage()];
        }
    }
}
