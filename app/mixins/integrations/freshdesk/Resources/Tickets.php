<?php

namespace App\Mixins\Integrations\Freshdesk\Resources;

final class Tickets extends Resource
{
    public function create(array $payload): array
    {
        $fields      = $payload['fields'] ?? $payload;
        $attachments = $payload['attachments'] ?? [];
        return $this->client->post('/tickets', $fields, $attachments)['data'] ?? [];
    }

    public function get(int $id, array $include = []): array
    {
        $query = $include ? ['include' => implode(',', $include)] : [];
        return $this->client->get("/tickets/{$id}", $query)['data'] ?? [];
    }

    public function list(array $query = []): array
    {
        return $this->client->get('/tickets', $query);
    }

    public function update(int $id, array $payload): array
    {
        return $this->client->put("/tickets/{$id}", $payload)['data'] ?? [];
    }

    public function delete(int $id): void
    {
        $this->client->delete("/tickets/{$id}");
    }

    public function restore(int $id): void
    {
        $this->client->put("/tickets/{$id}/restore");
    }

    public function search(string $query, int $page = 1): array
    {
        return $this->client->get('/search/tickets', [
            'query' => "\"{$query}\"",
            'page'  => $page,
        ]);
    }

    public function bulkUpdate(array $ids, array $properties): array
    {
        return $this->client->put('/tickets/bulk_update', [
            'bulk_action' => ['ids' => $ids, 'properties' => $properties],
        ])['data'] ?? [];
    }

    public function bulkDelete(array $ids): array
    {
        return $this->client->post('/tickets/bulk_delete', [
            'bulk_action' => ['ids' => $ids],
        ])['data'] ?? [];
    }

    public function merge(int $primaryId, array $ids, array $options = []): array
    {
        $body = array_merge(
            ['primary_id' => $primaryId, 'ticket_ids' => $ids],
            $options,
        );
        return $this->client->post('/tickets/merge', $body)['data'] ?? [];
    }

    public function forward(int $id, array $payload): array
    {
        return $this->client->post("/tickets/{$id}/forward", $payload)['data'] ?? [];
    }

    public function outboundEmail(array $payload): array
    {
        return $this->client->post('/tickets/outbound_email', $payload)['data'] ?? [];
    }

    public function allUpdatedSince(\DateTimeInterface $since): \Generator
    {
        return $this->paginateAll('/tickets', [
            'updated_since' => $since->format('c'),
            'order_by'      => 'updated_at',
            'order_type'    => 'asc',
        ]);
    }
}
