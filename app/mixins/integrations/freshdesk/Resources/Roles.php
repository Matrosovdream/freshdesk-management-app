<?php

namespace App\Mixins\Integrations\Freshdesk\Resources;

final class Roles extends Resource
{
    public function get(int $id): array
    {
        return $this->client->get("/roles/{$id}")['data'] ?? [];
    }

    public function list(array $query = []): array
    {
        return $this->client->get('/roles', $query);
    }

    public function all(): \Generator
    {
        return $this->paginateAll('/roles');
    }
}
