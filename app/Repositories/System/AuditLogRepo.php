<?php

namespace App\Repositories\System;

use App\Models\AuditLog;
use App\Repositories\AbstractRepo;

class AuditLogRepo extends AbstractRepo
{
    public function __construct()
    {
        $this->model = new AuditLog();
    }

    public function record(array $entry): array
    {
        $item = $this->model->create($entry);

        return $this->mapItem($item->fresh());
    }

    public function paginateForTarget(string $type, int $id, $paginate = 50)
    {
        $query = $this->model
            ->where('target_type', $type)
            ->where('target_id', $id)
            ->orderByDesc('created_at');

        return $this->mapItems($query->paginate($paginate));
    }

    public function mapItem($item)
    {
        if (empty($item)) {
            return null;
        }

        return [
            'id'          => $item->id,
            'user_id'     => $item->user_id,
            'actor_type'  => $item->actor_type,
            'action'      => $item->action,
            'target_type' => $item->target_type,
            'target_id'   => $item->target_id,
            'source'      => $item->source,
            'ip_address'  => $item->ip_address,
            'created_at'  => $item->created_at,
            'Model'       => $item,
        ];
    }
}
