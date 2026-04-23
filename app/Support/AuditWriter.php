<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

final class AuditWriter
{
    public static function log(string $action, ?string $targetType = null, ?int $targetId = null, array $before = [], array $after = [], array $meta = []): AuditLog
    {
        return AuditLog::create([
            'user_id'        => Auth::id(),
            'actor_type'     => Auth::check() ? 'user' : 'system',
            'action'         => $action,
            'target_type'    => $targetType,
            'target_id'      => $targetId,
            'source'         => Request::is('rest/*') ? 'rest' : 'web',
            'payload_before' => $before ?: null,
            'payload_after'  => $after ?: null,
            'meta'           => $meta ?: null,
            'ip_address'     => Request::ip(),
            'user_agent'     => Request::userAgent(),
        ]);
    }
}
