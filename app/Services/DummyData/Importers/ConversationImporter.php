<?php

namespace App\Services\DummyData\Importers;

use App\Models\Conversation;
use App\Models\Ticket;
use App\Repositories\Ticket\ConversationRepo;
use App\Services\DummyData\DummyDataLoader;

class ConversationImporter
{
    public function __construct(
        private DummyDataLoader $loader,
        private ConversationRepo $conversationRepo,
    ) {}

    public function import(): int
    {
        $ticketMap = Ticket::query()->pluck('id', 'freshdesk_id')->all();
        $count = 0;

        foreach ($this->loader->load('conversations.json') as $payload) {
            $fdId = $payload['id'] ?? null;
            $fdTicketId = $payload['ticket_id'] ?? null;

            if (!$fdId || !$fdTicketId) {
                continue;
            }

            $ticketId = $ticketMap[$fdTicketId] ?? null;
            if ($ticketId === null) {
                continue;
            }

            if (Conversation::query()->where('freshdesk_id', $fdId)->exists()) {
                continue;
            }

            $this->conversationRepo->upsertFromFreshdesk($payload, (int) $ticketId);
            $count++;
        }

        return $count;
    }
}
