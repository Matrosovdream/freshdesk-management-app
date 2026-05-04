<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Models\Ticket;
use App\Support\AuditWriter;
use Illuminate\Support\Facades\DB;

final class MergeContactsAction
{
    public function handle(array $data = []): array
    {
        $primaryId    = (int) ($data['primary_id'] ?? 0);
        $secondaryIds = array_map('intval', (array) ($data['secondary_ids'] ?? []));
        $primary = Contact::findOrFail($primaryId);

        DB::transaction(function () use ($primary, $secondaryIds) {

            foreach ($secondaryIds as $sid) {

                if ($sid === $primary->id) continue;

                $s = Contact::find($sid);
                if (! $s) continue;

                // Reassign any tickets to the primary, then soft-delete the secondary
                Ticket::where('requester_id', $s->id)->update(['requester_id' => $primary->id]);

                // Delete the secondary contact
                $s->delete();

                // Log the merge action for auditing
                AuditWriter::log('contact.merged', 'Contact', $s->id, [], ['into' => $primary->id]);
            }

        });

        return $primary->fresh('company')->toArray();
    }
}
