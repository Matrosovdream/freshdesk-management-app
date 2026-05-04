<?php

namespace App\Actions\System\Freshdesk;

use App\Models\Setting;
use App\Support\AuditWriter;
use Illuminate\Support\Facades\Crypt;

final class UpdateConnectionAction
{
    public function handle(array $data = []): array
    {
        if (!empty($data['domain'])) {
            Setting::updateOrCreate(['key' => 'freshdesk.domain'], ['value' => $data['domain'], 'type' => 'string', 'group' => 'freshdesk']);
        }
        if (!empty($data['api_key'])) {
            Setting::updateOrCreate(['key' => 'freshdesk.api_key'], ['value' => Crypt::encryptString($data['api_key']), 'type' => 'encrypted', 'group' => 'freshdesk']);
            Setting::updateOrCreate(['key' => 'freshdesk.test_ok'], ['value' => '0', 'type' => 'boolean', 'group' => 'freshdesk']);
        }

        AuditWriter::log('freshdesk.connection_updated', null, null, [], ['domain' => $data['domain'] ?? null]);
        
        return app(GetConnectionAction::class)->handle();
    }
}
