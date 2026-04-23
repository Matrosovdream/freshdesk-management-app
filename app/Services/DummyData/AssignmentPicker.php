<?php

namespace App\Services\DummyData;

use App\Models\Role;
use App\Models\User;

class AssignmentPicker
{
    /** @var array<int, int> */
    private array $managerUserIds = [];

    /** @var array<int, int> */
    private array $nonAdminUserIds = [];

    public function __construct()
    {
        $managerRoleId = Role::query()->where('slug', 'manager')->value('id');

        if ($managerRoleId !== null) {
            $this->managerUserIds = User::query()
                ->whereHas('roles', fn ($q) => $q->where('roles.id', $managerRoleId))
                ->where('is_active', true)
                ->orderBy('id')
                ->pluck('id')
                ->all();
        }

        $this->nonAdminUserIds = User::query()
            ->where('is_active', true)
            ->whereDoesntHave('roles', fn ($q) => $q->where('slug', 'superadmin'))
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }

    /** @return array<int, int> */
    public function managerUserIds(): array
    {
        return $this->managerUserIds;
    }

    /** @return array<int, int> */
    public function nonAdminUserIds(): array
    {
        return $this->nonAdminUserIds;
    }

    public function randomNonAdminUserId(): ?int
    {
        if (empty($this->nonAdminUserIds)) {
            return null;
        }

        return $this->nonAdminUserIds[array_rand($this->nonAdminUserIds)];
    }
}
