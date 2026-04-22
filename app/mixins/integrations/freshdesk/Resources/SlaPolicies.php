<?php

namespace App\Mixins\Integrations\Freshdesk\Resources;

final class SlaPolicies extends Resource
{
    public function list(array $query = []): array
    {
        return $this->client->get('/sla_policies', $query);
    }

    public function all(): \Generator
    {
        return $this->paginateAll('/sla_policies');
    }
}
