<?php

namespace App\Actions\Groups;

use App\Models\Group;
use App\Support\ApiQuery;
use App\Support\ManagerScope;

final class ListGroupsAction
{
    public function handle(array $data = []): array
    {
        $q = Group::query();
        ManagerScope::applyToGroups($q);

        ApiQuery::applySearch($q, $data['search'] ?? null, ['name']);
        ApiQuery::applyOrderBy($q, $data['sort'] ?? null, ['name', 'fd_updated_at'], 'name');

        return ApiQuery::page($q, $data);
    }
}
