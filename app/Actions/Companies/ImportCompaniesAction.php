<?php

namespace App\Actions\Companies;

use App\Models\Company;
use App\Models\SyncJob;
use App\Support\AuditWriter;
use Illuminate\Http\UploadedFile;

final class ImportCompaniesAction
{
    public function handle(array $data = []): array
    {
        /** @var UploadedFile|null $file */
        $file = $data['file'] ?? request()->file('file');

        $job = SyncJob::create([
            'resource' => 'companies.import', 'mode' => 'manual',
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
                    $name = $assoc['name'] ?? null;
                    if (! $name) { $failed++; continue; }
                    $max = (int) Company::max('freshdesk_id');
                    $domains = array_values(array_filter(array_map('trim', explode(',', $assoc['domains'] ?? ''))));
                    Company::updateOrCreate(
                        ['name' => $name],
                        [
                            'description'  => $assoc['description'] ?? null,
                            'domains'      => $domains,
                            'industry'     => $assoc['industry'] ?? null,
                            'account_tier' => $assoc['account_tier'] ?? null,
                            'health_score' => $assoc['health_score'] ?? null,
                            'freshdesk_id' => $max > 0 ? $max + 1 : 1_000_000,
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

        AuditWriter::log('companies.imported', null, null, [], ['job_id' => $job->id]);
        return ['job_id' => $job->id, 'upserted' => $upserted, 'failed' => $failed];
    }
}
