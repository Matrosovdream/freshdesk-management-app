<?php

namespace App\Mixins\Integrations\Freshdesk\Testing;

use App\Mixins\Integrations\Freshdesk\Client;
use App\Mixins\Integrations\Freshdesk\Config;
use App\Mixins\Integrations\Freshdesk\Exceptions\NotFoundException;
use App\Mixins\Integrations\Freshdesk\RateLimitGuard;
use App\Mixins\Integrations\Freshdesk\ResponseParser;
use App\Mixins\Integrations\Freshdesk\RetryPolicy;

class FakeClient extends Client
{
    public array $calls     = [];
    public array $responses = [];

    public function __construct()
    {
        parent::__construct(
            new Config(new \App\Repositories\System\SettingRepo()),
            new RateLimitGuard(new Config(new \App\Repositories\System\SettingRepo())),
            new RetryPolicy(),
            new ResponseParser(),
        );
    }

    public function queueResponse(string $method, string $path, array $response): void
    {
        $this->responses[strtoupper($method) . ' ' . $path][] = $response;
    }

    public function get(string $path, array $query = []): array
    {
        return $this->record('GET', $path, ['query' => $query]);
    }

    public function post(string $path, array $body = [], array $files = []): array
    {
        return $this->record('POST', $path, ['body' => $body, 'files' => $files]);
    }

    public function put(string $path, array $body = []): array
    {
        return $this->record('PUT', $path, ['body' => $body]);
    }

    public function delete(string $path, array $body = []): void
    {
        $this->record('DELETE', $path, ['body' => $body]);
    }

    public function paginate(string $path, array $query = []): \Generator
    {
        yield $this->record('GET', $path, ['query' => $query]);
    }

    private function record(string $method, string $path, array $options): array
    {
        $key = strtoupper($method) . ' ' . $path;
        $this->calls[] = ['method' => $method, 'path' => $path, 'options' => $options];

        if (isset($this->responses[$key]) && count($this->responses[$key]) > 0) {
            return array_shift($this->responses[$key]);
        }

        return ['data' => [], 'meta' => ['page' => 1, 'per_page' => 0, 'total' => 0, 'next' => null]];
    }

    public function assertCalled(string $method, string $path): void
    {
        foreach ($this->calls as $call) {
            if (strtoupper($call['method']) === strtoupper($method) && $call['path'] === $path) {
                return;
            }
        }

        throw new \PHPUnit\Framework\AssertionFailedError("Expected call not found: {$method} {$path}");
    }

    public function reset(): void
    {
        $this->calls     = [];
        $this->responses = [];
    }
}
