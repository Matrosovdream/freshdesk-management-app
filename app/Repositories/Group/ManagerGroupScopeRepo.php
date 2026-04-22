<?php

namespace App\Repositories\Group;

use App\Models\ManagerGroupScope;
use App\Repositories\AbstractRepo;

class ManagerGroupScopeRepo extends AbstractRepo
{
    public function __construct()
    {
        $this->model = new ManagerGroupScope();
    }

    public function groupIdsForUser(int $userId): array
    {
        return $this->model
            ->where('user_id', $userId)
            ->pluck('group_id')
            ->all();
    }

    public function sync(int $userId, array $groupIds): void
    {
        $this->model->where('user_id', $userId)->delete();

        $rows = collect($groupIds)->unique()->map(fn ($gid) => [
            'user_id'    => $userId,
            'group_id'   => $gid,
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        if (!empty($rows)) {
            $this->model->insert($rows);
        }
    }

    public function mapItem($item)
    {
        if (empty($item)) {
            return null;
        }

        return [
            'id'       => $item->id,
            'user_id'  => $item->user_id,
            'group_id' => $item->group_id,
            'Model'    => $item,
        ];
    }
}
