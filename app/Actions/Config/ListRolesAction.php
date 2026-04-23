<?php

namespace App\Actions\Config;

use App\Models\Role;

final class ListRolesAction
{
    public function handle(array $data = []): array
    {
        return Role::orderBy('name')->get(['id', 'slug', 'name', 'description'])->toArray();
    }
}
