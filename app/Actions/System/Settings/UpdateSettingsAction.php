<?php

namespace App\Actions\System\Settings;

use App\Models\Setting;
use App\Support\AuditWriter;
use Illuminate\Support\Facades\Crypt;

final class UpdateSettingsAction
{
    public function handle(array $data = []): array
    {
        $updates = (array) ($data['updates'] ?? []);
        $before  = [];
        $after   = [];

        foreach ($updates as $u) {
            if (! isset($u['key'])) continue;
            $row = Setting::firstOrNew(['key' => $u['key']]);
            $before[$u['key']] = $row->value;

            $value = $u['value'] ?? null;
            if (is_array($value)) $value = json_encode($value);
            if (($row->type ?? null) === 'boolean') $value = $value ? '1' : '0';
            if (($row->type ?? null) === 'encrypted' && is_string($value) && $value !== '') {
                $value = Crypt::encryptString($value);
            }
            $row->value = (string) $value;
            $row->group = $row->group ?? 'general';
            $row->type  = $row->type  ?? 'string';
            $row->save();
            $after[$u['key']] = $row->value;
        }

        AuditWriter::log('settings.updated', null, null, $before, $after);
        return app(GetSettingsAction::class)->handle();
    }
}
