# 08 — Testing

End-to-end test coverage across three layers: **Unit** (isolated classes), **Feature** (HTTP-level integration), **Frontend** (Vue component + end-to-end). All tests run in CI before any merge.

Tooling:
- **PHPUnit 12** (already in `composer.json`) — unit + feature tests
- **Vitest** — frontend unit tests for Pinia stores, composables, and components
- **Playwright** — end-to-end browser tests for critical user flows
- **FakeClient** from [05-freshdesk-integration.md](05-freshdesk-integration.md) — zero network in feature tests
- Factories under `database/factories/` — one per model from [01-data-layer.md](01-data-layer.md)

---

## 0. Layout

```
tests/
├── TestCase.php                  # base case; boots Laravel, swaps Client ⇒ FakeClient
├── CreatesUsers.php              # trait: actingAsSuperadmin(), actingAsManager(), actingAsCustomer()
├── Unit/
│   ├── Repositories/
│   │   ├── UserRepoTest.php
│   │   ├── RoleRepoTest.php
│   │   ├── TicketRepoTest.php
│   │   ├── ContactRepoTest.php
│   │   ├── CompanyRepoTest.php
│   │   ├── AgentRepoTest.php
│   │   ├── GroupRepoTest.php
│   │   ├── ConversationRepoTest.php
│   │   ├── TimeEntryRepoTest.php
│   │   ├── SettingRepoTest.php
│   │   ├── SyncJobRepoTest.php
│   │   ├── AuditLogRepoTest.php
│   │   ├── ApiKeyRepoTest.php
│   │   └── PortalDraftRepoTest.php
│   ├── Services/
│   │   └── FreshdeskServiceTest.php     # façade exposes resources
│   ├── Mixins/Integrations/Freshdesk/
│   │   ├── ClientTest.php               # uses Http::fake()
│   │   ├── RateLimitGuardTest.php
│   │   ├── RetryPolicyTest.php
│   │   ├── SignatureVerifierTest.php
│   │   ├── ResponseParserTest.php
│   │   └── Resources/
│   │       ├── TicketsTest.php
│   │       ├── ContactsTest.php
│   │       ├── CompaniesTest.php
│   │       ├── AgentsTest.php
│   │       ├── GroupsTest.php
│   │       ├── ConversationsTest.php
│   │       └── TimeEntriesTest.php
│   ├── Actions/
│   │   ├── Tickets/*Test.php            # one per Action in app/Actions/Tickets
│   │   ├── Contacts/*Test.php
│   │   ├── Companies/*Test.php
│   │   ├── Agents/*Test.php
│   │   ├── Groups/*Test.php
│   │   ├── TimeEntries/*Test.php
│   │   ├── Sync/*Test.php
│   │   ├── System/*/*Test.php
│   │   ├── Portal/*/*Test.php
│   │   └── Webhooks/Freshdesk/*Test.php
│   ├── Support/
│   │   ├── RightsTest.php               # catalog() integrity, grouping
│   │   └── SignedUrlFactoryTest.php
│   └── Models/
│       └── UserTest.php                 # hasRole, hasRight, rights()
│
├── Feature/
│   ├── Web/
│   │   ├── SpaShellTest.php             # /dashboard/{any?} → shell; / redirects based on role
│   │   ├── DownloadTest.php             # signed-URL validation, expiry, tampering
│   │   └── SanctumCsrfTest.php
│   ├── Api/V1/
│   │   ├── Auth/
│   │   │   ├── AdminSessionTest.php
│   │   │   ├── PortalSessionTest.php
│   │   │   ├── PortalRegisterTest.php
│   │   │   ├── PortalMagicLinkTest.php
│   │   │   ├── PortalVerifyTest.php
│   │   │   └── PasswordResetTest.php
│   │   ├── Admin/
│   │   │   ├── OverviewTest.php
│   │   │   ├── TicketTest.php           # index/create/show/update/delete
│   │   │   ├── TicketBulkTest.php
│   │   │   ├── TicketConversationTest.php
│   │   │   ├── TicketTimeEntryTest.php
│   │   │   ├── ContactTest.php
│   │   │   ├── ContactImportTest.php
│   │   │   ├── ContactExportTest.php
│   │   │   ├── CompanyTest.php
│   │   │   ├── AgentTest.php
│   │   │   ├── GroupTest.php
│   │   │   ├── ReportsTest.php
│   │   │   ├── AuditLogTest.php
│   │   │   └── System/
│   │   │       ├── FreshdeskConnectionTest.php
│   │   │       ├── ManagerTest.php
│   │   │       ├── SyncJobTest.php
│   │   │       ├── SettingsTest.php
│   │   │       └── ApiKeyTest.php
│   │   └── Portal/
│   │       ├── HomeTest.php
│   │       ├── RequestTest.php          # submit, show, reply, resolve, reopen, rate
│   │       ├── DraftTest.php
│   │       └── ProfileTest.php
│   ├── Rest/V1/
│   │   ├── Webhooks/
│   │   │   ├── FreshdeskSignatureTest.php
│   │   │   ├── TicketCreatedTest.php
│   │   │   ├── TicketUpdatedTest.php
│   │   │   ├── TicketRepliedTest.php
│   │   │   └── ContactUpdatedTest.php
│   │   └── HealthTest.php
│   ├── Authorization/
│   │   ├── RoleMiddlewareTest.php       # role:superadmin|manager
│   │   ├── RightMiddlewareTest.php      # right:tickets.update
│   │   └── ManagerScopeTest.php         # forbids out-of-scope access everywhere
│   ├── RateLimit/
│   │   └── FreshdeskRateLimitTest.php   # 429 → backoff + retry, exception bubbling
│   └── Sync/
│       └── SyncJobIntegrationTest.php
│
└── Browser/                              # Playwright project lives here
    └── playwright.config.ts
```

Frontend unit tests sit next to their source: `resources/js/**/*.spec.ts`.

---

## 1. Base test case

`tests/TestCase.php`:

```php
use App\Mixins\Integrations\Freshdesk\Client;
use App\Mixins\Integrations\Freshdesk\Testing\FakeClient;

abstract class TestCase extends \Illuminate\Foundation\Testing\TestCase
{
    use CreatesApplication, CreatesUsers, DatabaseTransactions;

    protected FakeClient $freshdesk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freshdesk = new FakeClient();
        $this->app->instance(Client::class, $this->freshdesk);
    }
}
```

`CreatesUsers` trait:

```php
public function actingAsSuperadmin(array $attrs = []): User  { return $this->actingAsRole('superadmin', $attrs); }
public function actingAsManager(array $attrs = [], array $groupIds = []): User
{
    $user = $this->actingAsRole('manager', $attrs);
    $user->managerGroups()->sync($groupIds);
    return $user;
}
public function actingAsCustomer(array $attrs = []): User    { return $this->actingAsRole('customer', $attrs); }
```

Each helper creates the user via factory, attaches the role, and calls `$this->actingAs($user)`.

---

## 2. Factories

One per model listed in [01-data-layer.md](01-data-layer.md) under `database/factories/`:

- `UserFactory`, `RoleFactory`, `RoleRightFactory`
- `ContactFactory`, `CompanyFactory`, `AgentFactory`, `GroupFactory`
- `TicketFactory`, `ConversationFactory`, `TimeEntryFactory`
- `ManagerGroupScopeFactory`
- `SettingFactory`, `SyncJobFactory`, `AuditLogFactory`, `ApiKeyFactory`, `PortalDraftFactory`

Factories fill `freshdesk_id` with a unique fake int, set JSON `payload` to a realistic Freshdesk body (aligned with [api-payloads.md](api-payloads.md)), and default timestamps to `now()`. States:

- `TicketFactory::overdue()`, `::unassigned()`, `::resolved()`, `::status(int)`, `::priority(int)`, `::forGroup(Group)`, `::forRequester(Contact)`
- `ContactFactory::verified()`, `::blocked()`, `::deleted()`
- `AgentFactory::support()`, `::field()`, `::collaborator()`, `::available()`
- `UserFactory::superadmin()`, `::manager()`, `::customer($contactId)`

---

## 3. Unit tests

### 3.1 Repositories — pattern

For each repo: CRUD round-trip, `mapItem()` shape, each custom finder, `upsertFromFreshdesk()` merges correctly on conflict.

Example — `TicketRepoTest.php`:

```php
public function test_upsert_inserts_on_first_sight(): void
{
    $repo = app(TicketRepo::class);
    $payload = TicketFactory::new()->raw(['freshdesk_id' => 999]);
    $result = $repo->upsertFromFreshdesk($payload);

    $this->assertEquals(999, $result['freshdesk_id']);
    $this->assertDatabaseHas('tickets', ['freshdesk_id' => 999]);
}

public function test_upsert_updates_existing_on_conflict(): void { /* … */ }

public function test_scoped_to_groups_filters_by_group_id(): void
{
    $in  = Ticket::factory()->forGroup(Group::factory()->create(['id' => 10]))->create();
    $out = Ticket::factory()->forGroup(Group::factory()->create(['id' => 20]))->create();

    $results = app(TicketRepo::class)->scopedToGroups([10])['items'];

    $this->assertTrue($results->contains('id', $in->id));
    $this->assertFalse($results->contains('id', $out->id));
}

public function test_overdue_returns_tickets_with_past_due_by(): void { /* … */ }
public function test_unassigned_returns_tickets_with_null_responder(): void { /* … */ }
```

One test class per repo; tests named in the form `test_<method>_<behavior>()`.

### 3.2 Freshdesk integration

Uses `Http::fake()` to assert outgoing requests and `FakeClient` for action-level tests.

`ClientTest.php`:
- Sends `Authorization: Basic base64(api_key:X)`.
- Encodes body as JSON; attachments as multipart.
- Retries once on 5xx (default policy).
- Throws `ValidationException` on 400 with field errors.
- Throws `AuthenticationException` on 401.
- Throws `RateLimitedException` on 429 with `retry_after_seconds` from `Retry-After`.
- Updates local rate-limit bucket from `X-RateLimit-Remaining`.

`RateLimitGuardTest.php`:
- Acquires tokens; deducts correctly.
- Returns `wait_ms` when empty.
- Refills at the configured rate over time.
- Is keyed per account (single tenant = one bucket).

`RetryPolicyTest.php`:
- 429 waits exactly `Retry-After` seconds.
- 500 backs off `250ms, 500ms, 1s`.
- Stops after max attempts and bubbles the last exception.

`SignatureVerifierTest.php`:
- Valid HMAC returns true.
- Tampered body / header returns false.
- Empty/missing header returns false.

`Resources/TicketsTest.php` (representative — same pattern for each resource):
- `create()` sends correct path + body, maps response.
- `list()` forwards query params.
- `search()` URL-encodes and quotes the query string.
- `bulkUpdate()` wraps ids + properties under `bulk_action`.
- `allUpdatedSince()` yields pages concatenated.

### 3.3 Actions

Every Action gets a unit test. Action tests bind a fake repo (or use the real repo against in-memory DB with transactions) and a `FakeClient`. Assert: right methods called on Freshdesk, right mirror row written, right audit entry recorded, right domain exception thrown when Freshdesk rejects.

Example — `CreateTicketActionTest.php`:

```php
public function test_creates_on_freshdesk_and_upserts_mirror(): void
{
    $this->freshdesk->seedCreateTicket(['id' => 42, 'subject' => 'Hello']);
    $action = app(CreateTicketAction::class);

    $result = $action->handle(['subject' => 'Hello', 'description' => 'World', 'email' => 'x@y.z']);

    $this->assertEquals(42, $result['freshdesk_id']);
    $this->assertDatabaseHas('tickets', ['freshdesk_id' => 42, 'subject' => 'Hello']);
    $this->freshdesk->assertCalled('POST', '/tickets');
}

public function test_propagates_validation_errors_from_freshdesk(): void
{
    $this->freshdesk->failNext(new ValidationException(['email' => ['is invalid']]));
    $this->expectException(\Illuminate\Validation\ValidationException::class);
    app(CreateTicketAction::class)->handle(['subject' => 'X', 'email' => 'bad']);
}
```

One file per Action; usually 3–6 test methods (happy path + specific error paths).

### 3.4 Support / Models

- `RightsTest.php` — catalog keys match `role_rights.group`; every constant is listed in exactly one group; no duplicates.
- `UserTest.php` — `hasRole()`, `hasRight()` aggregate across multiple roles, deduplicate.

---

## 4. Feature tests

### 4.1 Conventions

- Every `/api/v1/*` endpoint has at least: happy path (2xx + JSON shape), auth failure (401), authz failure (403), validation failure (422).
- JSON shape assertions use `assertJsonStructure([...])` and spot-check key values with `assertJsonPath('data.id', $id)`.
- Manager-scoped endpoints include a test for the out-of-scope 403.

### 4.2 Auth

`AdminSessionTest.php`:
- `POST /admin/auth/login` with valid creds sets session cookie, returns `user`.
- Invalid creds → 401 generic.
- Disabled user → 403.
- Customer role trying to log in via admin → 403.
- `/admin/auth/me` returns the current user.
- `POST /admin/auth/logout` revokes session.

`PortalSessionTest.php`:
- Same pattern for portal login.
- Admin/manager trying to log in via portal → 403 (wrong surface).

`PortalRegisterTest.php`:
- 201 creates local user + Freshdesk contact (FakeClient asserts `POST /contacts` call).
- Duplicate email → 409 or 422.
- Feature flag off → 403.

`PortalMagicLinkTest.php`:
- Send → always 200 generic.
- Consume valid token → 200 sets session.
- Consume expired → 410.
- Replay → 410.

### 4.3 Admin endpoints — representative (tickets)

`TicketTest.php`:
- `POST /admin/tickets` — happy path creates locally + asserts FakeClient call → fills response.
- `GET /admin/tickets` — filters (status, priority, agent, group, updated_since) produce correct rows.
- `GET /admin/tickets/:id` — 404 unknown; 200 existing.
- `PUT /admin/tickets/:id` — updates mirror + Freshdesk.
- `DELETE` — soft-delete.
- `POST /admin/tickets/:id/restore` — restores.
- `POST /admin/tickets/bulk-update` — applies properties to N rows.
- `POST /admin/tickets/merge` — primary keeps identity; secondaries deleted.

Every mutating test asserts a matching `audit_log` row.

### 4.4 Portal endpoints

`RequestTest.php`:
- `POST /portal/requests` — sets `requester_id = user.freshdesk_contact_id`, `source = Portal`.
- `GET /portal/requests` — scoped to caller; filter chips map to right status ranges.
- `GET /portal/requests/:id` — 403 when not the requester's ticket; 200 otherwise.
- Company-view flag opens visibility to other requesters in the same company.
- `POST /portal/requests/:id/reply` — posts reply.
- `POST /portal/requests/:id/resolve` — sets status=4; subsequent CSAT allowed.
- `POST /portal/requests/:id/rate` — stores rating.
- `GET` request detail excludes internal notes (`private=true`).

### 4.5 Webhooks

`FreshdeskSignatureTest.php`:
- Missing header → 401.
- Wrong signature → 401.
- Valid signature → handler runs.

`TicketCreatedTest.php`:
- POST body → upserts ticket row + broadcasts Reverb event.
- Duplicate event (idempotency) → second call is a no-op.

### 4.6 Authorization

`RoleMiddlewareTest.php`:
- `role:superadmin` 403s manager and customer, 200s superadmin.

`RightMiddlewareTest.php`:
- `right:tickets.update` 403s users whose roles don't include it.

`ManagerScopeTest.php`:
- Manager requesting a ticket outside their assigned groups → 403.
- Contact list is filtered to those with in-scope tickets.
- Reports endpoints forward `assigned_group_ids` as query filter.

### 4.7 Rate limit

`FreshdeskRateLimitTest.php`:
- When `FakeClient` is configured to emit 429, the Action bubbles `RateLimitedException` → the controller returns 503 with `Retry-After` header.
- Sync jobs catch, log on `sync_jobs`, and re-queue with delay.

---

## 5. Frontend tests — Vitest

Run with `npm run test:unit` (added to `package.json`: `"test:unit": "vitest"`).

Layout (co-located):

```
resources/js/
├── shared/
│   ├── http.ts
│   └── http.spec.ts
├── apps/dashboard/
│   ├── stores/
│   │   ├── auth.ts
│   │   ├── auth.spec.ts
│   │   ├── tickets.ts
│   │   └── tickets.spec.ts
│   ├── pages/
│   │   └── tickets/
│   │       ├── TicketListPage.vue
│   │       └── TicketListPage.spec.ts
│   └── components/
│       ├── DataTable.vue
│       └── DataTable.spec.ts
└── apps/portal/
    └── (same shape)
```

### 5.1 Store tests

For each Pinia store: initial state, each action hits the right URL with the right payload, mutates state on success, handles errors. Mock axios with `msw` or `vi.mock('axios')`.

Example — `auth.spec.ts`:

```ts
it('logs in and stores user', async () => {
  server.use(
    http.post('/api/v1/admin/auth/login', () => HttpResponse.json({ user: { id: 1, email: 'a@b.c' } })),
  );
  const auth = useAuth();
  await auth.login({ email: 'a@b.c', password: 'x' });
  expect(auth.user?.id).toBe(1);
});

it('bubbles 401 to the caller', async () => { /* … */ });
```

### 5.2 Component tests

Use `@vue/test-utils`. Focus on interaction behavior, not markup.

- `DataTable.spec.ts` — sort toggles direction, row selection emits `select`, "Load more" triggers `load-more` event.
- `AssignPicker.spec.ts` — typing debounces then fires search, selecting emits value.
- `Composer.spec.ts` — attachments validated client-side, tab switch preserves body, submit button disabled while sending.
- `StatusPill.spec.ts` — status number → correct human label + color.
- `ConfirmModal.spec.ts` — typed-confirm requires exact match.
- `CsatPrompt.spec.ts` — score + comment submit, dismiss hides and stores flag.

### 5.3 Page tests

One integration-style test per page. Mount the page with a mocked router + store, assert key interactions.

- `LoginPage.spec.ts` (admin + portal variants) — valid submit calls `auth.login`, invalid shows inline error.
- `TicketListPage.spec.ts` — renders rows from the store, clicking a filter chip dispatches `fetch` with that filter.
- `NewTicketPage.spec.ts` — submit sends multipart when attachments present.
- `TicketDetailPage.spec.ts` — switching composer tab preserves text; sending reply prepends to thread; CSAT prompt appears on resolved ticket.
- `ContactListPage.spec.ts` — import opens modal; export triggers `window.location` to returned `download_url`.
- `SettingsPage.spec.ts` — encrypted fields are masked until "Edit"; save sends only dirty keys.

---

## 6. End-to-end — Playwright

Slim suite; only critical happy paths. Runs against a seeded test DB + `FakeClient`-bound backend.

`tests/Browser/specs/`:

- `admin-login-and-create-ticket.spec.ts` — log in → create ticket → see it in list → open detail → reply.
- `manager-scope.spec.ts` — manager sees only assigned groups; URL-typing forbidden page blocks out-of-scope ticket.
- `portal-submit-and-track.spec.ts` — register → verify → submit request → see in list → read agent reply (simulated via FakeClient) → rate.
- `portal-magic-link.spec.ts` — send magic link → consume → logged in.
- `api-key-create.spec.ts` — superadmin creates API key → copies → revokes.

Each test uses `test.beforeEach` to reset DB (`php artisan migrate:fresh --seed --env=testing`) and clear `FakeClient` state via a test-only endpoint under `/rest/v1/__test__/reset` (registered only when `APP_ENV=testing`).

Browsers: Chromium + WebKit by default; Firefox on nightly.

---

## 7. Coverage targets

| Layer | Target |
|---|---|
| Unit (backend) | **≥ 90%** line coverage on `app/Repositories`, `app/Services`, `app/Mixins`, `app/Actions` |
| Feature | Every route listed in [03-routes-controllers-actions.md](03-routes-controllers-actions.md) has at least one happy-path + one authz test |
| Frontend unit | **≥ 80%** on stores and shared components |
| E2E | 5 flows listed above, green on every PR |

Coverage collected via Xdebug (or PCOV for speed) on backend, `@vitest/coverage-v8` on frontend.

---

## 8. CI

`.github/workflows/test.yml` runs three jobs in parallel:

1. **backend** — `composer install`, `php artisan migrate --env=testing`, `php artisan test --coverage --min=90`
2. **frontend-unit** — `npm ci`, `npm run test:unit -- --coverage`
3. **e2e** — `npm ci`, `npm run build`, `php artisan serve &`, `npx playwright install --with-deps`, `npx playwright test`

All three must pass to merge. Coverage reports upload to Codecov.

---

## 9. Test data and fixtures

- `database/seeders/TestingSeeder.php` — minimal dataset: 1 superadmin, 1 manager (assigned to 1 group), 1 customer (linked to a Freshdesk contact), 5 tickets across the manager's group + 5 outside.
- `tests/fixtures/freshdesk/` — JSON files with canned Freshdesk response bodies for `Scenarios::ticket()`, `::contact()`, etc.

---

## 10. Scope of this section

**Done in step 08:**
- `tests/` tree created with one file per class/endpoint listed above
- `TestCase`, `CreatesUsers`, factories, testing seeder
- `FakeClient` + `Scenarios` wired into the container for all feature tests
- Vitest configured with `vitest.config.ts` and a `vitest.setup.ts`
- Playwright configured, 5 e2e specs
- GitHub Actions workflow
- Coverage reporting

**Not done here:**
- Filling in business-logic-specific assertions for features that haven't been implemented yet — those tests go red first (TDD-style) and turn green as each feature section lands
- Performance / load tests (separate concern)
- Visual-regression tests (optional, future)
