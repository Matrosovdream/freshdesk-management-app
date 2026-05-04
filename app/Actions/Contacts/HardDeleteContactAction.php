<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Support\AuditWriter;

final class HardDeleteContactAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        
        $c = Contact::withTrashed()->findOrFail($id);
        $c->forceDelete();

        // Log metadata for hard delete
        AuditWriter::log('contact.hard_deleted', 'Contact', $id);

        return ['id' => $id, 'hard_deleted' => true];
    }
}
