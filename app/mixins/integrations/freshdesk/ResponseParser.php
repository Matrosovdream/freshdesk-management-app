<?php

namespace App\Mixins\Integrations\Freshdesk;

use Illuminate\Http\Client\Response;

class ResponseParser
{
    public function parse(Response $response): array
    {
        $body = $response->json();
        if (!is_array($body)) {
            $body = $response->body() === '' ? null : $response->body();
        }

        return [
            'data' => $body,
            'meta' => $this->extractPagination($response),
        ];
    }

    public function extractPagination(Response $response): array
    {
        $link    = $response->header('Link');
        $page    = (int) ($response->header('X-Page') ?: 0);
        $perPage = (int) ($response->header('X-Per-Page') ?: 0);
        $total   = $response->header('X-Total') !== null ? (int) $response->header('X-Total') : null;

        return [
            'page'     => $page ?: null,
            'per_page' => $perPage ?: null,
            'total'    => $total,
            'next'     => $this->parseNextLink($link),
        ];
    }

    private function parseNextLink(?string $link): ?string
    {
        if (!$link) {
            return null;
        }

        if (preg_match('/<([^>]+)>;\s*rel="next"/', $link, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
