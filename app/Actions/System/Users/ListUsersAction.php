<?php

namespace App\Actions\System\Users;

use App\Models\User;
use App\Support\ApiQuery;

final class ListUsersAction
{
    public function handle(array $data = []): array
    {
        $q = User::query()
            ->with([
                'roles:id,slug,name',
                'managerGroups' => fn ($g) => $g->select('groups.id', 'groups.name'),
            ]);

        ApiQuery::applySearch($q, $data['search'] ?? null, ['name', 'email']);
        ApiQuery::applyOrderBy($q, $data['sort'] ?? null, ['name', 'email', 'last_login_at', 'created_at'], 'created_at');

        $res = ApiQuery::page($q, $data);
        $res['data'] = array_map(function ($u) {
            $hasPin = $u instanceof User ? !empty($u->getAttribute('pin')) : !empty(($u['pin'] ?? null));
            $arr = \is_array($u) ? $u : $u->toArray();
            $arr['assigned_groups'] = $arr['manager_groups'] ?? [];
            $arr['has_pin'] = $hasPin;
            unset($arr['manager_groups'], $arr['pin']);
            return $arr;
        }, $res['data']);

        return $res;
    }
}
