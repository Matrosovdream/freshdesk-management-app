<?php

namespace App\Mixins\Integrations\Freshdesk\Resources;

final class Conversations extends Resource
{
    public function listForTicket(int $ticketId, array $query = []): array
    {
        return $this->client->get("/tickets/{$ticketId}/conversations", $query);
    }

    public function reply(int $ticketId, array $payload): array
    {
        $fields      = $payload['fields'] ?? $payload;
        $attachments = $payload['attachments'] ?? [];
        return $this->client->post("/tickets/{$ticketId}/reply", $fields, $attachments)['data'] ?? [];
    }

    public function note(int $ticketId, array $payload): array
    {
        $fields      = $payload['fields'] ?? $payload;
        $attachments = $payload['attachments'] ?? [];
        return $this->client->post("/tickets/{$ticketId}/notes", $fields, $attachments)['data'] ?? [];
    }

    public function update(int $conversationId, array $payload): array
    {
        $fields      = $payload['fields'] ?? $payload;
        $attachments = $payload['attachments'] ?? [];
        if ($attachments) {
            return $this->client->post("/conversations/{$conversationId}", $fields, $attachments)['data'] ?? [];
        }
        return $this->client->put("/conversations/{$conversationId}", $fields)['data'] ?? [];
    }

    public function delete(int $conversationId): void
    {
        $this->client->delete("/conversations/{$conversationId}");
    }
}
