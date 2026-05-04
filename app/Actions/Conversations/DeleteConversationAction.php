<?php

namespace App\Actions\Conversations;

use App\Models\Conversation;
use App\Support\AuditWriter;

final class DeleteConversationAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);

        $conv = Conversation::findOrFail($id);
        $conv->delete();

        AuditWriter::log('conversation.deleted', 'Conversation', $id);
        
        return ['id' => $id, 'deleted' => true];
    }
}
