<?php

namespace App\Mixins\Integrations\Freshdesk\Resources;

final class Groups extends Resource
{
    public function create(array $payload): array
    {
        return $this->client->post('/groups', $payload)['data'] ?? [];
    }

    public function get(int $id): array
    {
        return $this->client->get("/groups/{$id}")['data'] ?? [];
    }

    public function list(array $query = []): array
    {
        return $this->client->get('/groups', $query);
    }

    public function update(int $id, array $payload): array
    {
        return $this->client->put("/groups/{$id}", $payload)['data'] ?? [];
    }

    public function delete(int $id): void
    {
        $this->client->delete("/groups/{$id}");
    }

    public function all(): \Generator
    {
        return $this->paginateAll('/groups');
    }
}
