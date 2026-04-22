<?php

namespace App\Mixins\Integrations\Freshdesk\Resources;

final class Automations extends Resource
{
    public function list(int $type, array $query = []): array
    {
        return $this->client->get("/automations/{$type}/rules", $query);
    }

    public function get(int $type, int $id): array
    {
        return $this->client->get("/automations/{$type}/rules/{$id}")['data'] ?? [];
    }

    public function create(int $type, array $payload): array
    {
        return $this->client->post("/automations/{$type}/rules", $payload)['data'] ?? [];
    }

    public function update(int $type, int $id, array $payload): array
    {
        return $this->client->put("/automations/{$type}/rules/{$id}", $payload)['data'] ?? [];
    }

    public function delete(int $type, int $id): void
    {
        $this->client->delete("/automations/{$type}/rules/{$id}");
    }
}
