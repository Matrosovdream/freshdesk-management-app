<?php

namespace App\Mixins\Integrations\Freshdesk\Resources;

final class Products extends Resource
{
    public function get(int $id): array
    {
        return $this->client->get("/products/{$id}")['data'] ?? [];
    }

    public function list(array $query = []): array
    {
        return $this->client->get('/products', $query);
    }

    public function all(): \Generator
    {
        return $this->paginateAll('/products');
    }
}
