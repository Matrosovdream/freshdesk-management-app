<?php

namespace App\Actions\Conversations;

use App\Models\Conversation;

final class ListConversationsAction
{
    public function handle(array $data = []): array
    {
        $ticketId = (int) ($data['ticket_id'] ?? 0);
        
        return Conversation::where('ticket_id', $ticketId)
            ->orderBy('fd_created_at', 'asc')
            ->get()
            ->toArray();
    }
}
