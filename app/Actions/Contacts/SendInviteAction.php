<?php

namespace App\Actions\Contacts;

use App\Models\Contact;
use App\Services\NotificationService;
use App\Support\AuditWriter;
use Illuminate\Support\Str;

final class SendInviteAction
{
    public function __construct(private NotificationService $notifications) {}

    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $contact = Contact::findOrFail($id);

        $token = Str::random(40);
        $meta = ['invite_token' => hash('sha256', $token), 'invited_at' => now()->toIso8601String()];
        $contact->payload = array_merge((array) $contact->payload, ['invite' => $meta]);
        $contact->save();

        $this->notifications->sendContactInvite($contact, $token);

        AuditWriter::log('contact.invite_sent', 'Contact', $contact->id, [], $meta);

        return [
            'id'      => $contact->id,
            'invited' => true,
            'token'   => $token,
        ];
    }
}
