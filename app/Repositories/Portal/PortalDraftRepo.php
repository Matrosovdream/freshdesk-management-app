<?php

namespace App\Repositories\Portal;

use App\Models\PortalDraft;
use App\Repositories\AbstractRepo;

class PortalDraftRepo extends AbstractRepo
{
    public function __construct()
    {
        $this->model = new PortalDraft();
    }

    public function getForUser(int $userId)
    {
        $item = $this->model->where('user_id', $userId)->orderByDesc('id')->first();

        return $this->mapItem($item);
    }

    public function replaceForUser(int $userId, array $payload): array
    {
        $this->model->where('user_id', $userId)->delete();

        $row = $this->model->create([
            'user_id' => $userId,
            'payload' => $payload,
        ]);

        return $this->mapItem($row->fresh());
    }

    public function clearForUser(int $userId): void
    {
        $this->model->where('user_id', $userId)->delete();
    }

    public function mapItem($item)
    {
        if (empty($item)) {
            return null;
        }

        return [
            'id'         => $item->id,
            'user_id'    => $item->user_id,
            'payload'    => $item->payload,
            'updated_at' => $item->updated_at,
            'Model'      => $item,
        ];
    }
}
