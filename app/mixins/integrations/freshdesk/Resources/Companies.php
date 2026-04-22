<?php

namespace App\Mixins\Integrations\Freshdesk\Resources;

final class Companies extends Resource
{
    public function create(array $payload): array
    {
        return $this->client->post('/companies', $payload)['data'] ?? [];
    }

    public function get(int $id): array
    {
        return $this->client->get("/companies/{$id}")['data'] ?? [];
    }

    public function list(array $query = []): array
    {
        return $this->client->get('/companies', $query);
    }

    public function update(int $id, array $payload): array
    {
        return $this->client->put("/companies/{$id}", $payload)['data'] ?? [];
    }

    public function delete(int $id): void
    {
        $this->client->delete("/companies/{$id}");
    }

    public function search(string $query, int $page = 1): array
    {
        return $this->client->get('/search/companies', [
            'query' => "\"{$query}\"",
            'page'  => $page,
        ]);
    }

    public function listContacts(int $companyId, array $query = []): array
    {
        return $this->client->get("/companies/{$companyId}/contacts", $query);
    }

    public function allUpdatedSince(\DateTimeInterface $since): \Generator
    {
        return $this->paginateAll('/companies', [
            '_updated_since' => $since->format('c'),
        ]);
    }
}
