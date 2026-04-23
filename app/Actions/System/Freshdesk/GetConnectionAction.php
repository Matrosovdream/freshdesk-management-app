<?php

namespace App\Actions\System\Freshdesk;

use App\Models\Setting;

final class GetConnectionAction
{
    public function handle(array $data = []): array
    {
        $keys = ['freshdesk.domain', 'freshdesk.test_ok', 'freshdesk.rate_limit_remaining', 'freshdesk.rate_limit_total', 'freshdesk.checked_at'];
        $rows = Setting::whereIn('key', $keys)->get()->keyBy('key');

        return [
            'domain'                 => optional($rows['freshdesk.domain'] ?? null)->value,
            'test_ok'                => (int) optional($rows['freshdesk.test_ok'] ?? null)->value === 1,
            'rate_limit_remaining'   => isset($rows['freshdesk.rate_limit_remaining']) ? (int) $rows['freshdesk.rate_limit_remaining']->value : null,
            'rate_limit_total'       => isset($rows['freshdesk.rate_limit_total']) ? (int) $rows['freshdesk.rate_limit_total']->value : null,
            'checked_at'             => optional($rows['freshdesk.checked_at'] ?? null)->value,
            'api_key_stored'         => (bool) Setting::where('key', 'freshdesk.api_key')->value('value'),
        ];
    }
}
