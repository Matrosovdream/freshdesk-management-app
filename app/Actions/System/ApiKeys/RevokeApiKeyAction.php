<?php

namespace App\Actions\System\ApiKeys;

use App\Models\ApiKey;
use App\Support\AuditWriter;

final class RevokeApiKeyAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        
        $key = ApiKey::findOrFail($id);
        $key->update(['revoked_at' => now()]);

        AuditWriter::log('api_key.revoked', 'ApiKey', $key->id);

        return ['id' => $key->id, 'revoked' => true];
    }
}
