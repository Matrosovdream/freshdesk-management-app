<?php

namespace App\Repositories\System;

use App\Models\ApiKey;
use App\Repositories\AbstractRepo;
use Illuminate\Support\Str;

class ApiKeyRepo extends AbstractRepo
{
    public function __construct()
    {
        $this->model = new ApiKey();
    }

    public function createKey(string $name, array $scopes, ?int $createdBy = null, ?\DateTimeInterface $expiresAt = null): array
    {
        $plain  = 'fk_' . Str::random(40);
        $prefix = substr($plain, 0, 10);
        $hash   = hash('sha256', $plain);

        $row = $this->model->create([
            'name'       => $name,
            'prefix'     => $prefix,
            'hash'       => $hash,
            'scopes'     => $scopes,
            'created_by' => $createdBy,
            'expires_at' => $expiresAt,
        ]);

        return array_merge($this->mapItem($row->fresh()), [
            'plaintext' => $plain, // returned exactly once to the caller
        ]);
    }

    public function findByHash(string $hash)
    {
        return $this->mapItem($this->model->where('hash', $hash)->first());
    }

    public function rotate(int $id): array
    {
        $row = $this->model->find($id);
        if (!$row) {
            return [];
        }

        $plain  = 'fk_' . Str::random(40);
        $prefix = substr($plain, 0, 10);
        $hash   = hash('sha256', $plain);

        $row->update(['prefix' => $prefix, 'hash' => $hash, 'revoked_at' => null]);

        return array_merge($this->mapItem($row->fresh()), [
            'plaintext' => $plain,
        ]);
    }

    public function revoke(int $id): void
    {
        $this->model->where('id', $id)->update(['revoked_at' => now()]);
    }

    public function mapItem($item)
    {
        if (empty($item)) {
            return null;
        }

        return [
            'id'           => $item->id,
            'name'         => $item->name,
            'prefix'       => $item->prefix,
            'scopes'       => $item->scopes,
            'created_by'   => $item->created_by,
            'last_used_at' => $item->last_used_at,
            'expires_at'   => $item->expires_at,
            'revoked_at'   => $item->revoked_at,
            'Model'        => $item,
        ];
    }
}
