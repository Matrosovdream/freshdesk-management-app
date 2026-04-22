<?php

namespace App\Repositories\System;

use App\Models\SyncJob;
use App\Repositories\AbstractRepo;

class SyncJobRepo extends AbstractRepo
{
    public function __construct()
    {
        $this->model = new SyncJob();
    }

    public function start(string $resource, string $mode = 'incremental'): array
    {
        $item = $this->model->create([
            'resource'   => $resource,
            'mode'       => $mode,
            'status'     => 'running',
            'started_at' => now(),
        ]);

        return $this->mapItem($item);
    }

    public function finish(int $jobId, int $processed, int $upserted, int $failed = 0): void
    {
        $this->model->where('id', $jobId)->update([
            'status'          => 'success',
            'finished_at'     => now(),
            'items_processed' => $processed,
            'items_upserted'  => $upserted,
            'items_failed'    => $failed,
        ]);
    }

    public function fail(int $jobId, string $error): void
    {
        $this->model->where('id', $jobId)->update([
            'status'      => 'failed',
            'finished_at' => now(),
            'error'       => $error,
        ]);
    }

    public function lastSuccessful(string $resource)
    {
        $item = $this->model
            ->where('resource', $resource)
            ->where('status', 'success')
            ->orderByDesc('finished_at')
            ->first();

        return $this->mapItem($item);
    }

    public function mapItem($item)
    {
        if (empty($item)) {
            return null;
        }

        return [
            'id'              => $item->id,
            'resource'        => $item->resource,
            'mode'            => $item->mode,
            'status'          => $item->status,
            'started_at'      => $item->started_at,
            'finished_at'     => $item->finished_at,
            'items_processed' => (int) $item->items_processed,
            'items_upserted'  => (int) $item->items_upserted,
            'items_failed'    => (int) $item->items_failed,
            'error'           => $item->error,
            'Model'           => $item,
        ];
    }
}
