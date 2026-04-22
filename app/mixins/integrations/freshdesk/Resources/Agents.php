<?php

namespace App\Mixins\Integrations\Freshdesk\Resources;

final class Agents extends Resource
{
    public function me(): array
    {
        return $this->client->get('/agents/me')['data'] ?? [];
    }

    public function create(array $payload): array
    {
        return $this->client->post('/agents', $payload)['data'] ?? [];
    }

    public function get(int $id): array
    {
        return $this->client->get("/agents/{$id}")['data'] ?? [];
    }

    public function list(array $query = []): array
    {
        return $this->client->get('/agents', $query);
    }

    public function update(int $id, array $payload): array
    {
        return $this->client->put("/agents/{$id}", $payload)['data'] ?? [];
    }

    public function delete(int $id): void
    {
        $this->client->delete("/agents/{$id}");
    }

    public function bulkCreate(array $payload): array
    {
        return $this->client->post('/agents/bulk', $payload)['data'] ?? [];
    }

    public function all(): \Generator
    {
        return $this->paginateAll('/agents');
    }
}
