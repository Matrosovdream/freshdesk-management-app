<?php

namespace App\Mixins\Integrations\Freshdesk;

use App\Mixins\Integrations\Freshdesk\Exceptions\AuthenticationException;
use App\Mixins\Integrations\Freshdesk\Exceptions\AuthorizationException;
use App\Mixins\Integrations\Freshdesk\Exceptions\ConnectionException;
use App\Mixins\Integrations\Freshdesk\Exceptions\FreshdeskException;
use App\Mixins\Integrations\Freshdesk\Exceptions\NotFoundException;
use App\Mixins\Integrations\Freshdesk\Exceptions\RateLimitedException;
use App\Mixins\Integrations\Freshdesk\Exceptions\ServerException;
use App\Mixins\Integrations\Freshdesk\Exceptions\ValidationException;
use Illuminate\Http\Client\ConnectionException as HttpConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Client
{
    public function __construct(
        protected Config $config,
        protected RateLimitGuard $guard,
        protected RetryPolicy $retry,
        protected ResponseParser $parser,
    ) {}

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, ['query' => $query]);
    }

    public function post(string $path, array $body = [], array $files = []): array
    {
        return $this->request('POST', $path, ['body' => $body, 'files' => $files]);
    }

    public function put(string $path, array $body = []): array
    {
        return $this->request('PUT', $path, ['body' => $body]);
    }

    public function delete(string $path, array $body = []): void
    {
        $this->request('DELETE', $path, ['body' => $body]);
    }

    public function paginate(string $path, array $query = []): \Generator
    {
        $page = max(1, (int) ($query['page'] ?? 1));

        while (true) {
            $query['page'] = $page;
            $response = $this->request('GET', $path, ['query' => $query]);
            yield $response;

            $next = $response['meta']['next'] ?? null;
            if (!$next) {
                $data = $response['data'];
                if (!is_array($data) || count($data) === 0) {
                    break;
                }
                $perPage = (int) ($query['per_page'] ?? 30);
                if (count($data) < $perPage) {
                    break;
                }
            }

            $page++;
        }
    }

    protected function request(string $method, string $path, array $options): array
    {
        $interactive = ($options['interactive'] ?? true) !== false;
        $this->guard->acquire($interactive);

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->retry->maxAttempts()) {
            $attempt++;

            try {
                $response = $this->send($method, $path, $options);
                $this->guard->reportRemaining($this->extractRemaining($response));

                if ($response->successful()) {
                    return $this->parser->parse($response);
                }

                $status = $response->status();

                if ($this->retry->shouldRetry($attempt, $status, null)) {
                    $retryAfter = (int) $response->header('Retry-After');
                    $this->retry->sleep($this->retry->waitMs($attempt, $status, $retryAfter ?: null));
                    continue;
                }

                $this->throwForStatus($response);
            } catch (HttpConnectionException $e) {
                $lastException = new ConnectionException($e->getMessage(), 0, $e);

                if ($this->retry->shouldRetry($attempt, null, $e)) {
                    $this->retry->sleep($this->retry->waitMs($attempt, null, null));
                    continue;
                }

                throw $lastException;
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        throw new FreshdeskException('Exhausted retries without success.');
    }

    protected function send(string $method, string $path, array $options): Response
    {
        $request = $this->pendingRequest($options);
        $url     = $this->buildUrl($path);

        return match (strtoupper($method)) {
            'GET'    => $request->get($url, $options['query'] ?? []),
            'POST'   => $request->post($url, $options['body'] ?? []),
            'PUT'    => $request->put($url, $options['body'] ?? []),
            'DELETE' => $request->delete($url, $options['body'] ?? []),
            default  => throw new FreshdeskException("Unsupported method: {$method}"),
        };
    }

    protected function pendingRequest(array $options): PendingRequest
    {
        $apiKey = $this->config->apiKey();
        if (!$apiKey) {
            throw new AuthenticationException('Freshdesk API key is not configured.');
        }

        $request = Http::withBasicAuth($apiKey, 'X')
            ->acceptJson()
            ->timeout(30);

        if (!empty($options['files'])) {
            foreach ($options['files'] as $file) {
                $request = $request->attach(
                    $file['name'] ?? 'attachments[]',
                    $file['contents'],
                    $file['filename'] ?? null,
                    $file['headers'] ?? [],
                );
            }
            $request = $request->asMultipart();
        } else {
            $request = $request->asJson();
        }

        return $request;
    }

    protected function buildUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $this->config->baseUrl() . '/' . ltrim($path, '/');
    }

    protected function extractRemaining(Response $response): ?int
    {
        $value = $response->header('X-RateLimit-Remaining');
        return $value !== null && $value !== '' ? (int) $value : null;
    }

    protected function throwForStatus(Response $response): void
    {
        $status = $response->status();
        $body   = $response->json() ?? [];
        $message = is_array($body) ? ($body['description'] ?? $body['message'] ?? $response->body()) : (string) $body;

        match (true) {
            $status === 401 => throw new AuthenticationException($message ?: 'Unauthorized', $status, null, ['body' => $body]),
            $status === 403 => throw new AuthorizationException($message ?: 'Forbidden', $status, null, ['body' => $body]),
            $status === 404 => throw new NotFoundException($message ?: 'Not found', $status, null, ['body' => $body]),
            $status === 400 || $status === 422 => throw new ValidationException(
                $message ?: 'Validation failed',
                $status,
                null,
                is_array($body) ? ($body['errors'] ?? []) : [],
                ['body' => $body],
            ),
            $status === 429 => throw new RateLimitedException(
                $message ?: 'Rate limited',
                $status,
                null,
                (int) ($response->header('Retry-After') ?: 0),
                ['body' => $body],
            ),
            $status >= 500 => throw new ServerException($message ?: 'Server error', $status, null, ['body' => $body]),
            default        => throw new FreshdeskException($message ?: "HTTP {$status}", $status, null, ['body' => $body]),
        };
    }
}
