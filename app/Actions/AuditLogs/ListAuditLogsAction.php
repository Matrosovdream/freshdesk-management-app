<?php

namespace App\Actions\AuditLogs;

use App\Models\AuditLog;
use App\Support\ApiQuery;

final class ListAuditLogsAction
{
    public function handle(array $data = []): array
    {
        $q = AuditLog::query()->with('user');

        if (!empty($data['user_id']))     $q->where('user_id', (int) $data['user_id']);
        if (!empty($data['action_type']) || !empty($data['action'])) {
            $q->where('action', 'like', '%'.($data['action_type'] ?? $data['action']).'%');
        }
        if (!empty($data['target_type'])) $q->where('target_type', $data['target_type']);
        if (!empty($data['from']))        $q->where('created_at', '>=', $data['from']);
        if (!empty($data['to']))          $q->where('created_at', '<=', $data['to']);

        ApiQuery::applySearch($q, $data['search'] ?? null, ['action', 'target_type']);
        ApiQuery::applyOrderBy($q, $data['sort'] ?? null, ['id', 'created_at'], 'created_at');

        return ApiQuery::page($q, $data);
    }
}
