<?php

namespace App\Actions\Overview;

use App\Models\SyncJob;
use App\Support\AuditWriter;

final class RefreshOverviewAction
{
    public function handle(array $data = []): array
    {
        // Queue a sync-all job record. Real job dispatch happens in the sync step.
        $job = SyncJob::create([
            'resource'   => 'overview.refresh',
            'mode'       => 'manual',
            'status'     => 'queued',
            'started_at' => now(),
        ]);

        AuditWriter::log('overview.refresh_queued', null, null, [], ['job_id' => $job->id]);
        return ['queued' => true, 'job_id' => $job->id];
    }
}
