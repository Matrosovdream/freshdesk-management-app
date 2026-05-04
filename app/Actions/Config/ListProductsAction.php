<?php

namespace App\Actions\Config;

use App\Models\Setting;

final class ListProductsAction
{
    public function handle(array $data = []): array
    {
        $json = Setting::where('key', 'config.products')->value('value');
        $decoded = json_decode((string) $json, true);
        
        return is_array($decoded) ? $decoded : [];
    }
}
