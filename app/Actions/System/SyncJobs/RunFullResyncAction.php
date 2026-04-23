<?php

namespace App\Actions\System\SyncJobs;

use App\Models\SyncJob;
use App\Support\AuditWriter;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class RunFullResyncAction
{
    public function handle(array $data = []): array
    {
        if (($data['confirm'] ?? null) !== 'RESYNC') {
            throw new UnprocessableEntityHttpException("Type 'RESYNC' to confirm.");
        }

        $job = SyncJob::create([
            'resource'   => 'full_resync',
            'mode'       => 'manual',
            'status'     => 'queued',
            'started_at' => now(),
        ]);

        AuditWriter::log('sync.full_resync_queued', 'SyncJob', $job->id);
        return ['job_id' => $job->id, 'queued' => true];
    }
}
