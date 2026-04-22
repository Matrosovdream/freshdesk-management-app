<?php

namespace App\Mixins\Integrations\Freshdesk\Resources;

use App\Mixins\Integrations\Freshdesk\Client;

abstract class Resource
{
    public function __construct(protected Client $client) {}

    protected function paginateAll(string $path, array $query = []): \Generator
    {
        foreach ($this->client->paginate($path, $query) as $page) {
            $items = $page['data'] ?? [];
            if (!is_array($items)) {
                continue;
            }
            foreach ($items as $item) {
                yield $item;
            }
        }
    }
}
