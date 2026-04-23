<?php

namespace App\Actions\Agents;

use App\Models\Agent;
use App\Support\ApiQuery;
use App\Support\ManagerScope;

final class ListAgentsAction
{
    public function handle(array $data = []): array
    {
        $q = Agent::query();
        ManagerScope::applyToAgents($q);

        if (!empty($data['available'])) $q->where('available', (bool) $data['available']);

        ApiQuery::applySearch($q, $data['search'] ?? ($data['autocomplete'] ?? null), ['name', 'email']);
        ApiQuery::applyOrderBy($q, $data['sort'] ?? null, ['name', 'email', 'fd_updated_at'], 'name');

        return ApiQuery::page($q, $data);
    }
}
