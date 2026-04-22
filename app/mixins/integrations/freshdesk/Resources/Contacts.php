<?php

namespace App\Mixins\Integrations\Freshdesk\Resources;

final class Contacts extends Resource
{
    public function create(array $payload): array
    {
        return $this->client->post('/contacts', $payload)['data'] ?? [];
    }

    public function get(int $id): array
    {
        return $this->client->get("/contacts/{$id}")['data'] ?? [];
    }

    public function list(array $query = []): array
    {
        return $this->client->get('/contacts', $query);
    }

    public function update(int $id, array $payload): array
    {
        return $this->client->put("/contacts/{$id}", $payload)['data'] ?? [];
    }

    public function softDelete(int $id): void
    {
        $this->client->delete("/contacts/{$id}");
    }

    public function hardDelete(int $id): void
    {
        $this->client->delete("/contacts/{$id}/hard_delete", ['force' => true]);
    }

    public function restore(int $id): void
    {
        $this->client->put("/contacts/{$id}/restore");
    }

    public function sendInvite(int $id): array
    {
        return $this->client->put("/contacts/{$id}/send_invite")['data'] ?? [];
    }

    public function makeAgent(int $id, array $payload = []): array
    {
        return $this->client->put("/contacts/{$id}/make_agent", $payload)['data'] ?? [];
    }

    public function merge(int $primaryId, array $secondaryIds, array $contactFields = []): array
    {
        return $this->client->post('/contacts/merge', [
            'primary_contact_id'   => $primaryId,
            'secondary_contact_ids' => $secondaryIds,
            'contact'              => $contactFields,
        ])['data'] ?? [];
    }

    public function search(string $query, int $page = 1): array
    {
        return $this->client->get('/search/contacts', [
            'query' => "\"{$query}\"",
            'page'  => $page,
        ]);
    }

    public function allUpdatedSince(\DateTimeInterface $since): \Generator
    {
        return $this->paginateAll('/contacts', [
            '_updated_since' => $since->format('c'),
        ]);
    }
}
