<?php

namespace App\Repositories\User;

use App\Models\UserSetting;
use App\Repositories\AbstractRepo;

class UserSettingRepo extends AbstractRepo
{
    public function __construct()
    {
        $this->model = new UserSetting();
    }

    public function find(int $userId, string $key): ?UserSetting
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('key', $key)
            ->first();
    }

    public function upsert(int $userId, string $key, ?string $value): UserSetting
    {
        return $this->model->updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value],
        );
    }

    /**
     * @return array<string, ?string> map of key => raw stored value
     */
    public function allFor(int $userId): array
    {
        return $this->model
            ->where('user_id', $userId)
            ->get(['key', 'value'])
            ->mapWithKeys(fn ($r) => [$r->key => $r->value])
            ->all();
    }

    public function deleteForUser(int $userId, string $key): bool
    {
        return (bool) $this->model
            ->where('user_id', $userId)
            ->where('key', $key)
            ->delete();
    }
}
