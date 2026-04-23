<?php

namespace App\Actions\System\ApiKeys;

use App\Models\ApiKey;

final class ListApiKeysAction
{
    public function handle(array $data = []): array
    {
        return ApiKey::with('creator:id,name,email')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($k) => [
                'id'         => $k->id,
                'name'       => $k->name,
                'prefix'     => $k->prefix,
                'scopes'     => $k->scopes ?? [],
                'created_by' => $k->creator ? ['id' => $k->creator->id, 'name' => $k->creator->name] : null,
                'last_used_at' => optional($k->last_used_at)->toIso8601String(),
                'expires_at'   => optional($k->expires_at)->toIso8601String(),
                'status'       => $k->revoked_at ? 'revoked' : 'active',
            ])
            ->all();
    }
}
