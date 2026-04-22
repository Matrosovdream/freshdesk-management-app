<?php

namespace App\Mixins\Integrations\Freshdesk\Resources;

final class TimeEntries extends Resource
{
    public function listForTicket(int $ticketId): array
    {
        return $this->client->get("/tickets/{$ticketId}/time_entries");
    }

    public function list(array $query = []): array
    {
        return $this->client->get('/time_entries', $query);
    }

    public function create(int $ticketId, array $payload): array
    {
        return $this->client->post("/tickets/{$ticketId}/time_entries", $payload)['data'] ?? [];
    }

    public function update(int $id, array $payload): array
    {
        return $this->client->put("/time_entries/{$id}", $payload)['data'] ?? [];
    }

    public function delete(int $id): void
    {
        $this->client->delete("/time_entries/{$id}");
    }

    public function toggleTimer(int $id): array
    {
        return $this->client->put("/time_entries/{$id}/toggle_timer")['data'] ?? [];
    }
}
