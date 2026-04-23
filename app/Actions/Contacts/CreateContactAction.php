<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Support\AuditWriter;

final class CreateContactAction
{
    public function handle(array $data = []): array
    {
        $max = (int) Contact::max('freshdesk_id');
        $payload = array_intersect_key($data, array_flip([
            'name', 'email', 'phone', 'mobile', 'twitter_id', 'unique_external_id',
            'company_id', 'job_title', 'language', 'time_zone', 'address', 'active',
            'view_all_tickets', 'other_emails', 'other_companies', 'tags', 'custom_fields',
        ]));
        $payload['freshdesk_id']  = $max > 0 ? $max + 1 : 1_000_000;
        $payload['fd_created_at'] = now();
        $payload['fd_updated_at'] = now();

        $c = Contact::create($payload);
        AuditWriter::log('contact.created', 'Contact', $c->id, [], $c->toArray());
        return $c->fresh('company')->toArray();
    }
}
