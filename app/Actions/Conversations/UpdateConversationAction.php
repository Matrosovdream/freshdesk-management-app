<?php

namespace App\Actions\Conversations;

use App\Models\Conversation;
use App\Support\AuditWriter;

final class UpdateConversationAction
{
    public function handle(array $data = []): array
    {
        $id = (int) ($data['id'] ?? 0);
        $conv = Conversation::findOrFail($id);
        $before = $conv->toArray();

        $patch = array_intersect_key($data, array_flip(['body', 'body_text', 'private']));
        if (isset($patch['body'])) $patch['body_text'] = strip_tags($patch['body']);
        $conv->fill($patch)->save();

        AuditWriter::log('conversation.updated', 'Conversation', $conv->id, $before, $conv->fresh()->toArray());
        return $conv->fresh()->toArray();
    }
}
