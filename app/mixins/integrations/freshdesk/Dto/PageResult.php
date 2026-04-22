<?php

namespace App\Mixins\Integrations\Freshdesk\Dto;

final class PageResult
{
    public function __construct(
        public array $items = [],
        public ?int $page = null,
        public ?int $perPage = null,
        public ?int $total = null,
        public ?string $next = null,
    ) {}

    public static function fromResponse(array $response): self
    {
        $data = $response['data'] ?? [];
        $meta = $response['meta'] ?? [];

        return new self(
            items:   is_array($data) ? $data : [],
            page:    $meta['page']     ?? null,
            perPage: $meta['per_page'] ?? null,
            total:   $meta['total']    ?? null,
            next:    $meta['next']     ?? null,
        );
    }
}
