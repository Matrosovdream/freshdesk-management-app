<?php

namespace App\Mixins\Integrations\Freshdesk\Resources;

final class TicketFields extends Resource
{
    public function list(array $query = []): array
    {
        return $this->client->get('/ticket_fields', $query);
    }

    public function get(int $id): array
    {
        return $this->client->get("/ticket_fields/{$id}")['data'] ?? [];
    }

    public function create(array $payload): array
    {
        return $this->client->post('/admin/ticket_fields', $payload)['data'] ?? [];
    }

    public function update(int $id, array $payload): array
    {
        return $this->client->put("/admin/ticket_fields/{$id}", $payload)['data'] ?? [];
    }

    public function delete(int $id): void
    {
        $this->client->delete("/admin/ticket_fields/{$id}");
    }
}
