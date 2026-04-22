<?php

namespace App\Mixins\Integrations\Freshdesk\Resources;

final class BusinessHours extends Resource
{
    public function get(int $id): array
    {
        return $this->client->get("/business_hours/{$id}")['data'] ?? [];
    }

    public function list(array $query = []): array
    {
        return $this->client->get('/business_hours', $query);
    }

    public function all(): \Generator
    {
        return $this->paginateAll('/business_hours');
    }
}
