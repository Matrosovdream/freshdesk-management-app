<?php

namespace App\Actions\Agents;

use App\Models\Agent;
use App\Models\SyncJob;
use App\Support\AuditWriter;
use Illuminate\Http\UploadedFile;

final class BulkCreateAgentsAction
{
    public function handle(array $data = []): array
    {
        /** @var UploadedFile|null $file */
        $file = $data['file'] ?? request()->file('file');

        $job = SyncJob::create([
            'resource' => 'agents.bulk', 'mode' => 'manual',
            'status' => 'running', 'started_at' => now(),
        ]);

        $upserted = 0;
        $failed   = 0;

        if ($file && is_file($file->getRealPath())) {

            $fh = fopen($file->getRealPath(), 'r');
            $headers = fgetcsv($fh) ?: [];
            $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);

            while (($row = fgetcsv($fh)) !== false) {

                try {
                    $assoc = array_combine($headers, $row) ?: [];
                    $email = $assoc['email'] ?? null;

                    if (! $email) { $failed++; continue; }

                    $max = (int) Agent::max('freshdesk_id');

                    Agent::updateOrCreate(
                        ['email' => $email],
                        [
                            'name'          => $assoc['name'] ?? $email,
                            'ticket_scope'  => (int) ($assoc['ticket_scope'] ?? 1),
                            'occasional'    => false,
                            'available'     => true,
                            'type'          => 'support_agent',
                            'freshdesk_id'  => $max > 0 ? $max + 1 : 1_000_000,
                            'fd_created_at' => now(),
                            'fd_updated_at' => now(),
                        ]
                    );
                    $upserted++;
                    
                } catch (\Throwable $e) { $failed++; }

            }
            fclose($fh);

        }

        $job->update([
            'status' => 'success', 'finished_at' => now(),
            'items_processed' => $upserted + $failed,
            'items_upserted' => $upserted, 'items_failed' => $failed,
        ]);

        AuditWriter::log('agents.bulk_created', null, null, [], ['job_id' => $job->id]);
        return ['job_id' => $job->id, 'upserted' => $upserted, 'failed' => $failed];
    }
}
