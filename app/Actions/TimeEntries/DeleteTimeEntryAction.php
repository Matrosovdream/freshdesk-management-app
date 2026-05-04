<?php

namespace App\Actions\TimeEntries;

use App\Models\TimeEntry;
use App\Support\AuditWriter;

final class DeleteTimeEntryAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        
        $entry = TimeEntry::findOrFail($id);
        $entry->delete();

        AuditWriter::log('time_entry.deleted', 'TimeEntry', $id);

        return ['id' => $id, 'deleted' => true];
    }
}
