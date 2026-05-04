<?php

namespace App\Actions\Config;

use App\Models\Setting;

final class ListBusinessHoursAction
{
    public function handle(array $data = []): array
    {
        $json = Setting::where('key', 'config.business_hours')->value('value');
        $decoded = json_decode((string) $json, true);
        
        return is_array($decoded) ? $decoded : [
            ['id' => 1, 'name' => 'Default (24/7)', 'timezone' => 'UTC'],
        ];
    }
}
