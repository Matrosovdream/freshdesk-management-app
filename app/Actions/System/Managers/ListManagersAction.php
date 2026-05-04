<?php

namespace App\Actions\System\Managers;

use App\Models\User;
use App\Support\ApiQuery;

final class ListManagersAction
{
    public function handle(array $data = []): array
    {
        $q = User::query()
            ->whereHas('roles', fn ($r) => $r->where('slug', 'manager'))
            ->with(['managerGroups' => fn ($g) => $g->select('groups.id', 'groups.name')]);

        ApiQuery::applySearch($q, $data['search'] ?? null, ['name', 'email']);
        ApiQuery::applyOrderBy($q, $data['sort'] ?? null, ['name', 'email', 'last_login_at', 'created_at'], 'created_at');

        $res = ApiQuery::page($q, $data);
        $res['data'] = array_map(function ($u) {
            if (!is_array($u)) $u = $u->toArray();
            $u['assigned_groups'] = $u['manager_groups'] ?? [];
            unset($u['manager_groups']);
            return $u;
        }, $res['data']);
        
        return $res;
    }
}
