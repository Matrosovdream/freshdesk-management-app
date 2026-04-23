<?php

namespace App\Actions\TimeEntries;

use App\Models\TimeEntry;
use App\Support\AuditWriter;

final class UpdateTimeEntryAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $entry = TimeEntry::findOrFail($id);
        $before = $entry->toArray();

        $patch = array_intersect_key($data, array_flip(['time_spent', 'note', 'billable', 'timer_running', 'executed_at', 'agent_id']));
        if (array_key_exists('timer_running', $patch)) {
            $patch['start_time'] = $patch['timer_running'] ? now() : null;
        }
        $entry->fill($patch)->save();

        AuditWriter::log('time_entry.updated', 'TimeEntry', $entry->id, $before, $entry->fresh()->toArray());
        return $entry->fresh()->toArray();
    }
}
