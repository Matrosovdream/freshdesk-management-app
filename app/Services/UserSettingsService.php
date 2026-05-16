<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\User\UserSettingRepo;

class UserSettingsService
{
    public function __construct(private UserSettingRepo $repo) {}

    public function get(User $user, string $key, mixed $default = null): mixed
    {
        $row = $this->repo->find($user->id, $key);
        if (! $row) {
            return $default;
        }
        return $this->decode($row->value);
    }

    public function set(User $user, string $key, mixed $value): void
    {
        $this->repo->upsert($user->id, $key, $this->encode($value));
    }

    /**
     * Merge a key=>value map into the user's settings. Pass null to delete a key.
     */
    public function setMany(User $user, array $map): void
    {
        foreach ($map as $key => $value) {
            if ($value === null) {
                $this->forget($user, (string) $key);
            } else {
                $this->set($user, (string) $key, $value);
            }
        }
    }

    public function forget(User $user, string $key): bool
    {
        return $this->repo->deleteForUser($user->id, $key);
    }

    /**
     * @return array<string, mixed> decoded key => value map
     */
    public function all(User $user): array
    {
        $raw = $this->repo->allFor($user->id);
        return array_map(fn ($v) => $this->decode($v), $raw);
    }

    private function encode(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function decode(?string $stored): mixed
    {
        if ($stored === null) {
            return null;
        }
        $decoded = json_decode($stored, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $stored;
    }
}
