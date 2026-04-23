<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Support\AuditWriter;

final class RestoreContactAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $c = Contact::withTrashed()->findOrFail($id);
        $c->restore();
        AuditWriter::log('contact.restored', 'Contact', $id);
        return $c->fresh()->toArray();
    }
}
