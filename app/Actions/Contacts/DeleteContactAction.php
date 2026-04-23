<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Support\AuditWriter;

final class DeleteContactAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $c = Contact::findOrFail($id);
        $c->delete();
        AuditWriter::log('contact.deleted', 'Contact', $id);
        return ['id' => $id, 'deleted' => true];
    }
}
