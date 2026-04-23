<?php

namespace App\Actions\System\ApiKeys;

use App\Models\ApiKey;
use App\Support\AuditWriter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final class CreateApiKeyAction
{
    public function handle(array $data = []): array
    {
        $plain  = 'fk_'.Str::random(40);
        $prefix = substr($plain, 0, 11); // fk_ + 8 chars

        $key = ApiKey::create([
            'name'       => $data['name'] ?? 'API key',
            'prefix'     => $prefix,
            'hash'       => hash('sha256', $plain),
            'scopes'     => (array) ($data['scopes'] ?? []),
            'created_by' => Auth::id(),
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        AuditWriter::log('api_key.created', 'ApiKey', $key->id, [], ['prefix' => $prefix, 'scopes' => $key->scopes]);

        return ['id' => $key->id, 'prefix' => $prefix, 'plaintext' => $plain, 'scopes' => $key->scopes];
    }
}
