<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Support\AuditWriter;

final class UpdateContactAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $c = Contact::findOrFail($id);
        $before = $c->toArray();

        $patch = array_intersect_key($data, array_flip([
            'name', 'email', 'phone', 'mobile', 'twitter_id', 'unique_external_id',
            'company_id', 'job_title', 'language', 'time_zone', 'address', 'active',
            'view_all_tickets', 'other_emails', 'other_companies', 'tags', 'custom_fields',
        ]));
        $c->fill($patch);
        $c->fd_updated_at = now();
        $c->save();

        AuditWriter::log('contact.updated', 'Contact', $c->id, $before, $c->fresh()->toArray());
        return $c->fresh('company')->toArray();
    }
}
