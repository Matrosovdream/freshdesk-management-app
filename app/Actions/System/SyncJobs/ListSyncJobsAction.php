<?php

namespace App\Actions\System\SyncJobs;

use App\Models\Setting;
use App\Models\SyncJob;

final class ListSyncJobsAction
{
    public function handle(array $data = []): array
    {
        $resources = ['tickets', 'contacts', 'companies', 'agents', 'groups', 'conversations', 'time_entries'];

        $intervals = [];
        foreach ($resources as $r) {
            $intervals[$r] = Setting::where('key', "sync.{$r}_interval")->value('value');
        }

        $jobs = SyncJob::orderByDesc('id')->limit(200)->get()->map(fn ($j) => [
            'id'              => $j->id,
            'resource'        => $j->resource,
            'mode'            => $j->mode,
            'status'          => $j->status,
            'started_at'      => optional($j->started_at)->toIso8601String(),
            'finished_at'     => optional($j->finished_at)->toIso8601String(),
            'duration_ms'     => ($j->started_at && $j->finished_at) ? $j->started_at->diffInMilliseconds($j->finished_at) : null,
            'items_processed' => $j->items_processed,
            'items_upserted'  => $j->items_upserted,
            'items_failed'    => $j->items_failed,
            'error'           => $j->error,
        ])->all();

        return ['jobs' => $jobs, 'intervals' => $intervals];
    }
}
