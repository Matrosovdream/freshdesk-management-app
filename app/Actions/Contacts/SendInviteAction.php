<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Support\AuditWriter;
use Illuminate\Support\Str;

final class SendInviteAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $c = Contact::findOrFail($id);

        // Real invite delivery is wired in later (mailable + portal user creation).
        // For now: mark the contact as invited and record a token that can be consumed.
        $token = Str::random(40);
        $meta = ['invite_token' => hash('sha256', $token), 'invited_at' => now()->toIso8601String()];
        $c->payload = array_merge((array) $c->payload, ['invite' => $meta]);
        $c->save();

        AuditWriter::log('contact.invite_sent', 'Contact', $c->id, [], $meta);

        return [
            'id'       => $c->id,
            'invited'  => true,
            'token'    => $token, // returned once for integration tests; drop when email is wired
        ];
    }
}
