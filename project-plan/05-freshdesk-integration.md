# 05 — Freshdesk Integration

Wires the Freshdesk API into the app. Lives in two places:

- **`app/Services/FreshdeskService.php`** — the single façade that the rest of the app talks to. Injected into Actions and Sync jobs.
- **`app/mixins/integrations/freshdesk/`** — the integration guts: HTTP client, rate-limit guard, resource classes, DTOs, exceptions, webhook signature verifier. Nothing outside this folder reaches Freshdesk directly.

The same convention applies to any future integrations: each one gets its own folder under `app/mixins/integrations/{name}` and its own service class under `app/Services/`.

---

## 0. Composer autoload

Add a namespace mapping to [composer.json](../composer.json) so PSR-4 resolves the lowercase integration folders to `App\Mixins\…` classes:

```json
"autoload": {
    "psr-4": {
        "App\\": "app/",
        "App\\Mixins\\": "app/mixins/"
    }
}
```

Run `composer dump-autoload` after saving.

---

## 1. Folder layout

```
app/
├── Services/
│   └── FreshdeskService.php                   # public façade — the only thing Actions inject
│
└── mixins/
    └── integrations/
        └── freshdesk/
            ├── Client.php                     # low-level HTTP client (Laravel Http::)
            ├── Config.php                     # reads domain + api_key from SettingRepo
            ├── RateLimitGuard.php             # Redis token bucket per account
            ├── RetryPolicy.php                # exponential backoff + 429 Retry-After
            ├── ResponseParser.php             # maps response → arrays / pagination meta
            ├── SignatureVerifier.php          # validates incoming webhook HMAC
            │
            ├── Resources/                      # one class per Freshdesk resource
            │   ├── Resource.php                # shared base (request helpers, attachments)
            │   ├── Tickets.php
            │   ├── Conversations.php
            │   ├── TimeEntries.php
            │   ├── Contacts.php
            │   ├── Companies.php
            │   ├── Agents.php
            │   ├── Groups.php
            │   ├── Roles.php
            │   ├── TicketFields.php
            │   ├── Products.php
            │   ├── BusinessHours.php
            │   ├── SlaPolicies.php
            │   └── Automations.php
            │
            ├── Dto/                            # typed payload objects
            │   ├── TicketPayload.php
            │   ├── ConversationPayload.php
            │   ├── ContactPayload.php
            │   ├── CompanyPayload.php
            │   ├── AgentPayload.php
            │   ├── GroupPayload.php
            │   ├── TimeEntryPayload.php
            │   └── PageResult.php              # { items: [], page, per_page, total?, next? }
            │
            ├── Enums/
            │   ├── TicketStatus.php            # 2..5
            │   ├── TicketPriority.php          # 1..4
            │   └── TicketSource.php
            │
            ├── Exceptions/
            │   ├── FreshdeskException.php      # base
            │   ├── ConnectionException.php     # transport-level (DNS, TLS, timeout)
            │   ├── AuthenticationException.php # 401
            │   ├── AuthorizationException.php  # 403
            │   ├── NotFoundException.php       # 404
            │   ├── ValidationException.php     # 400/422 with field errors
            │   ├── RateLimitedException.php    # 429
            │   └── ServerException.php         # 5xx
            │
            ├── Testing/
            │   ├── FakeClient.php              # in-memory implementation for tests
            │   └── Scenarios.php               # canned payloads
            │
            └── FreshdeskServiceProvider.php    # bindings (optional — main binding is in AppServiceProvider)
```

Classes use PascalCase; folders stay lowercase per project convention. Namespace root is `App\Mixins\Integrations\Freshdesk`.

---

## 2. `App\Services\FreshdeskService` — the façade

```php
namespace App\Services;

use App\Mixins\Integrations\Freshdesk\Client;
use App\Mixins\Integrations\Freshdesk\Resources\{
    Tickets, Conversations, TimeEntries,
    Contacts, Companies, Agents, Groups, Roles,
    TicketFields, Products, BusinessHours, SlaPolicies, Automations
};

class FreshdeskService
{
    public function __construct(private Client $client) {}

    public function tickets(): Tickets              { return new Tickets($this->client); }
    public function conversations(): Conversations  { return new Conversations($this->client); }
    public function timeEntries(): TimeEntries      { return new TimeEntries($this->client); }
    public function contacts(): Contacts            { return new Contacts($this->client); }
    public function companies(): Companies          { return new Companies($this->client); }
    public function agents(): Agents                { return new Agents($this->client); }
    public function groups(): Groups                { return new Groups($this->client); }
    public function roles(): Roles                  { return new Roles($this->client); }
    public function ticketFields(): TicketFields    { return new TicketFields($this->client); }
    public function products(): Products            { return new Products($this->client); }
    public function businessHours(): BusinessHours  { return new BusinessHours($this->client); }
    public function slaPolicies(): SlaPolicies      { return new SlaPolicies($this->client); }
    public function automations(): Automations      { return new Automations($this->client); }

    public function ping(): array                   { return $this->agents()->me(); }  // used by the "Test connection" button
}
```

Actions/jobs inject it:

```php
public function __construct(
    private TicketRepo $tickets,
    private FreshdeskService $freshdesk,
) {}

public function handle(array $data): array
{
    $remote = $this->freshdesk->tickets()->create($data);
    return $this->tickets->upsertFromFreshdesk($remote);
}
```

---

## 3. `Client` — low-level HTTP

Thin wrapper around `Illuminate\Support\Facades\Http`. Every request:

1. Pulls `domain` + `api_key` from `Config` (which reads via `SettingRepo`, cached 60s).
2. Calls `RateLimitGuard::acquire()` (throws `RateLimitedException` on local bucket exhaustion; otherwise blocks briefly or gives up per policy).
3. Issues the request with `Basic base64(api_key:X)` auth, JSON content-type, attachments as multipart.
4. Pipes the response through `RetryPolicy` (honors `Retry-After` on 429; exponential backoff on 5xx).
5. Hands the raw response to `ResponseParser::parse()` → `['data' => …, 'meta' => ['page', 'per_page', 'total', 'next']]`.
6. Maps non-2xx responses to the typed exceptions in `Exceptions/`.
7. Records the last seen `X-RateLimit-Remaining` on a Redis key `fd:ratelimit:last` for display in `/dashboard/system/freshdesk`.

Public API (simplified):

```php
public function get(string $path, array $query = []): array;
public function post(string $path, array $body = [], array $files = []): array;
public function put(string $path, array $body = []): array;
public function delete(string $path): void;
public function paginate(string $path, array $query = []): \Generator;   // yields pages
```

---

## 4. `RateLimitGuard`

Token bucket in Redis, keyed per Freshdesk account (single tenant → one bucket). Configurable from `settings`:

- `freshdesk.plan_limit_per_hour` — default 3000 (Blossom/Garden), set to 5000 for Estate/Forest.
- Refill rate: `limit / 3600` tokens per second.
- On `acquire()`: deduct a token; if none available, compute `wait_ms` and either `usleep` (interactive) or throw `RateLimitedException` (background).
- On response, update the bucket from `X-RateLimit-Remaining` (authoritative from Freshdesk).

Interactive calls (from Actions) wait up to 2s; sync jobs throw and are rescheduled by Horizon.

---

## 5. `RetryPolicy`

- **429** — read `Retry-After`, wait exactly that long, retry up to 3 times.
- **5xx** — exponential backoff `250ms, 500ms, 1s`, up to 3 retries.
- **Transport/connect errors** — same backoff as 5xx.
- **4xx other than 429** — no retry; wrap in the matching exception.

---

## 6. `Resource` base + example

```php
namespace App\Mixins\Integrations\Freshdesk\Resources;

use App\Mixins\Integrations\Freshdesk\Client;

abstract class Resource
{
    public function __construct(protected Client $client) {}

    protected function paginateAll(string $path, array $query = []): \Generator
    {
        foreach ($this->client->paginate($path, $query) as $page) {
            yield from $page['data'];
        }
    }
}
```

### `Resources/Tickets.php`

Every method below maps 1:1 to an endpoint from [api-methods.md](api-methods.md) and uses the payload shapes from [api-payloads.md](api-payloads.md).

```php
final class Tickets extends Resource
{
    public function create(array $payload): array                     { return $this->client->post('/tickets', $payload['fields'] ?? $payload, $payload['attachments'] ?? []); }
    public function get(int $id, array $include = []): array          { return $this->client->get("/tickets/{$id}", $include ? ['include' => implode(',', $include)] : []); }
    public function list(array $query = []): array                    { return $this->client->get('/tickets', $query); }
    public function update(int $id, array $payload): array            { return $this->client->put("/tickets/{$id}", $payload); }
    public function delete(int $id): void                             { $this->client->delete("/tickets/{$id}"); }
    public function restore(int $id): void                            { $this->client->post("/tickets/{$id}/restore"); }
    public function search(string $query, int $page = 1): array       { return $this->client->get('/search/tickets', ['query' => "\"{$query}\"", 'page' => $page]); }
    public function bulkUpdate(array $ids, array $properties): array  { return $this->client->put('/tickets/bulk_update', ['bulk_action' => ['ids' => $ids, 'properties' => $properties]]); }
    public function bulkDelete(array $ids): array                     { return $this->client->delete('/tickets/bulk_delete'); /* body via options */ }
    public function merge(int $primaryId, array $ids, array $options = []): array { return $this->client->post('/tickets/merge', ['primary_id' => $primaryId, 'ticket_ids' => $ids] + $options); }
    public function forward(int $id, array $payload): array           { return $this->client->post("/tickets/{$id}/forward", $payload); }
    public function outboundEmail(array $payload): array              { return $this->client->post('/tickets/outbound_email', $payload); }

    public function allUpdatedSince(\DateTimeInterface $since): \Generator
    {
        return $this->paginateAll('/tickets', ['updated_since' => $since->format('c'), 'order_by' => 'updated_at', 'order_type' => 'asc']);
    }
}
```

Other resource classes mirror this shape — one method per endpoint from [api-methods.md](api-methods.md).

---

## 7. DTOs (optional, typed payload builders)

Actions can pass raw arrays, but for the most-used endpoints we provide payload builders that validate at construction time:

```php
namespace App\Mixins\Integrations\Freshdesk\Dto;

final class TicketPayload
{
    public function __construct(
        public string $subject,
        public string $description,
        public int $status = 2,
        public int $priority = 1,
        public int $source = 2,
        public ?int $requesterId = null,
        public ?string $email = null,
        public ?int $responderId = null,
        public ?int $groupId = null,
        public ?int $companyId = null,
        public array $tags = [],
        public array $customFields = [],
        public array $attachments = [],
    ) {
        if ($requesterId === null && $email === null) {
            throw new \InvalidArgumentException('requester_id or email is required');
        }
    }

    public function toArray(): array { /* map to Freshdesk field names */ }
}
```

Used as:
```php
$this->freshdesk->tickets()->create((new TicketPayload(...))->toArray());
```

---

## 8. Exceptions

Wrap every failure into a typed exception so Actions can `catch` specifically:

- `ValidationException` carries `errors[]` from the Freshdesk body — the Action re-throws as `Illuminate\Validation\ValidationException` so FormRequests render them inline.
- `RateLimitedException` carries `retry_after_seconds`. Sync jobs use it to re-queue with delay.
- `AuthenticationException` → bubbles to `/dashboard/system/freshdesk` as "Invalid API key — please re-check the connection settings."

---

## 9. Webhook signature verifier

```php
namespace App\Mixins\Integrations\Freshdesk;

final class SignatureVerifier
{
    public function __construct(private Config $config) {}

    public function verify(string $rawBody, string $signatureHeader): bool
    {
        $expected = hash_hmac('sha256', $rawBody, $this->config->webhookSecret());
        return hash_equals($expected, $signatureHeader);
    }
}
```

Used by `App\Http\Middleware\VerifyWebhookSignature` on `/rest/v1/webhooks/freshdesk`.

---

## 10. Service container bindings

`AppServiceProvider::register()`:

```php
use App\Mixins\Integrations\Freshdesk\{Client, Config, RateLimitGuard, RetryPolicy, ResponseParser, SignatureVerifier};
use App\Services\FreshdeskService;

$this->app->singleton(Config::class);
$this->app->singleton(RateLimitGuard::class);
$this->app->singleton(RetryPolicy::class);
$this->app->singleton(ResponseParser::class);
$this->app->singleton(SignatureVerifier::class);

$this->app->singleton(Client::class, function ($app) {
    return new Client(
        config: $app->make(Config::class),
        guard:  $app->make(RateLimitGuard::class),
        retry:  $app->make(RetryPolicy::class),
        parser: $app->make(ResponseParser::class),
    );
});

$this->app->singleton(FreshdeskService::class);
```

In tests, bind `Client::class` to `FakeClient::class` to avoid real HTTP.

---

## 11. Usage patterns

### From Actions (interactive, short-wait on rate limit)

```php
final class CreateTicketAction
{
    public function __construct(
        private TicketRepo $tickets,
        private FreshdeskService $freshdesk,
    ) {}

    public function handle(array $data): array
    {
        $remote = $this->freshdesk->tickets()->create($data);
        return $this->tickets->upsertFromFreshdesk($remote);
    }
}
```

### From Sync jobs (bulk, use `paginate()` + tolerant of 429)

```php
final class SyncTicketsAction
{
    public function __construct(
        private TicketRepo $tickets,
        private SyncJobRepo $jobs,
        private FreshdeskService $freshdesk,
    ) {}

    public function handle(?Carbon $since = null): void
    {
        $job = $this->jobs->start('tickets', $since ? 'incremental' : 'full');
        try {
            foreach ($this->freshdesk->tickets()->allUpdatedSince($since ?? Carbon::createFromTimestamp(0)) as $row) {
                $this->tickets->upsertFromFreshdesk($row);
            }
            $this->jobs->finish($job['id'], /* counters */);
        } catch (\Throwable $e) {
            $this->jobs->fail($job['id'], $e->getMessage());
            throw $e;
        }
    }
}
```

---

## 12. Testing

- `FakeClient` implements the same public contract as `Client`, returns canned responses from `Scenarios::ticket()`, `::contact()` etc.
- Bind it via the service container in `TestCase::setUp()`; Actions are tested against it with zero network.
- Integration tests (tagged `@group integration`) use real HTTP against a dedicated Freshdesk sandbox domain set via `FRESHDESK_SANDBOX_*` env vars; excluded from the default `phpunit` run.

---

## 13. Scope of this section

**Done in step 05:**
- `composer.json` autoload updated + `dump-autoload`
- `App\Services\FreshdeskService` created
- Every file under `app/mixins/integrations/freshdesk/` listed above
- Container bindings in `AppServiceProvider`
- Webhook signature middleware wired to use `SignatureVerifier`
- `FakeClient` available for tests

**Not done here:**
- Sync jobs themselves (separate section: scheduling, Horizon wiring, incremental cursors)
- Action bodies that *use* the service (filled in per-feature sections)
- Admin UI for editing the connection (built in step 06 under `/dashboard/system/freshdesk`)
