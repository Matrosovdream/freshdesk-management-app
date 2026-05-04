<?php

namespace App\Actions\System\ApiKeys;

use App\Models\ApiKey;
use App\Support\AuditWriter;
use Illuminate\Support\Str;

final class RotateApiKeyAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $key = ApiKey::findOrFail($id);

        $plain  = 'fk_'.Str::random(40);
        $prefix = substr($plain, 0, 11);
        $key->update([
            'prefix' => $prefix,
            'hash'   => hash('sha256', $plain),
        ]);

        AuditWriter::log('api_key.rotated', 'ApiKey', $key->id, [], ['prefix' => $prefix]);
        
        return ['id' => $key->id, 'prefix' => $prefix, 'plaintext' => $plain];
    }
}
