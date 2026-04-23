<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use App\Support\AuditWriter;
use Illuminate\Support\Facades\DB;

final class MergeTicketsAction
{
    public function handle(array $data = []): array
    {
        $primaryId    = (int) ($data['primary_id'] ?? $data['id'] ?? 0);
        $secondaryIds = array_map('intval', (array) ($data['secondary_ids'] ?? []));
        $primary = Ticket::findOrFail($primaryId);

        DB::transaction(function () use ($primary, $secondaryIds) {
            foreach ($secondaryIds as $sid) {
                if ($sid === $primary->id) continue;
                $s = Ticket::find($sid);
                if (! $s) continue;
                $s->update(['parent_id' => $primary->id, 'status' => 5]);
                $s->delete();
                AuditWriter::log('ticket.merged', 'Ticket', $s->id, [], ['into' => $primary->id]);
            }
        });

        return $primary->fresh(['requester', 'responder', 'group', 'company'])->toArray();
    }
}
