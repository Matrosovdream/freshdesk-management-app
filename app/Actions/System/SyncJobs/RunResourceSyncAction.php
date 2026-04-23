<?php

namespace App\Actions\System\SyncJobs;

use App\Models\SyncJob;
use App\Support\AuditWriter;

final class RunResourceSyncAction
{
    public function handle(array $data = []): array
    {
        $resource = (string) ($data['resource'] ?? request()->route('resource') ?? 'unknown');

        $job = SyncJob::create([
            'resource'   => $resource,
            'mode'       => 'manual',
            'status'     => 'queued',
            'started_at' => now(),
        ]);

        AuditWriter::log('sync.queued', 'SyncJob', $job->id, [], ['resource' => $resource]);
        return ['job_id' => $job->id, 'queued' => true];
    }
}
