<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Support\AuditWriter;

final class UpdateContactAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $contact = Contact::findOrFail($id);
        $before = $contact->toArray();

        $patch = array_intersect_key($data, array_flip([
            'name', 'email', 'phone', 'mobile', 'twitter_id', 'unique_external_id',
            'company_id', 'job_title', 'language', 'time_zone', 'address', 'active',
            'view_all_tickets', 'other_emails', 'other_companies', 'tags', 'custom_fields',
        ]));
        $contact->fill($patch);
        $contact->fd_updated_at = now();
        $contact->save();

        AuditWriter::log('contact.updated', 'Contact', $contact->id, $before, $contact->fresh()->toArray());

        return $contact->fresh('company')->toArray();
    }
}
