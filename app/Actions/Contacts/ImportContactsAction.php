<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Models\SyncJob;
use App\Support\AuditWriter;
use Illuminate\Http\UploadedFile;

final class ImportContactsAction
{
    public function handle(array $data = []): array
    {
        /** @var UploadedFile|null $file */
        $file = $data['file'] ?? request()->file('file');

        $job = SyncJob::create([
            'resource'   => 'contacts.import',
            'mode'       => 'manual',
            'status'     => 'running',
            'started_at' => now(),
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
                    $name  = $assoc['name']  ?? null;
                    if (! $email && ! $name) { $failed++; continue; }

                    $max = (int) Contact::max('freshdesk_id');

                    Contact::updateOrCreate(
                        ['email' => $email],
                        [
                            'name'          => $name ?: ($email ?: 'Unnamed'),
                            'phone'         => $assoc['phone']  ?? null,
                            'job_title'     => $assoc['job_title'] ?? null,
                            'time_zone'     => $assoc['time_zone'] ?? null,
                            'active'        => true,
                            'freshdesk_id'  => $max > 0 ? $max + 1 : 1_000_000,
                            'fd_created_at' => now(),
                            'fd_updated_at' => now(),
                        ]
                    );

                    $upserted++;

                } catch (\Throwable $e) {
                    $failed++;
                }
            }
            fclose($fh);
        }

        $job->update([
            'status'          => 'success',
            'finished_at'     => now(),
            'items_processed' => $upserted + $failed,
            'items_upserted'  => $upserted,
            'items_failed'    => $failed,
        ]);

        AuditWriter::log('contacts.imported', null, null, [], ['job_id' => $job->id, 'upserted' => $upserted, 'failed' => $failed]);
        
        return ['job_id' => $job->id, 'upserted' => $upserted, 'failed' => $failed];
    }
}
