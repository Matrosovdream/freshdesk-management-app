# Architecture

Laravel 13 companion SaaS for a single Freshdesk account. Three surfaces (web dashboard, SPA API, webhook REST), a Repositories layer instead of direct Eloquent use, and thin controllers delegating to Actions.

---

## 1. Repositories

Location: [app/Repositories/](../app/Repositories/)

Rule: **Controllers and Actions never touch `App\Models\*` directly.** They go through repos. Models stay as data carriers.

Every repo follows the existing pattern from [app/Repositories/User/UserRepo.php](../app/Repositories/User/UserRepo.php):

- Lives in a domain folder: `app/Repositories/{Domain}/{Name}Repo.php`
- Class name ends with `Repo` (e.g. `TicketRepo`, not `TicketRepository`)
- Extends [AbstractRepo](../app/Repositories/AbstractRepo.php) — inherits `getByID`, `getAll`, `getFirst`, `count`, `exists`, `create`, `update`, `delete`, `syncItemsUpsert`, `applyFilter`, `applySorting`
- Constructor sets `$this->model = new ModelName()`
- Set `$withRelations` for default eager-loads
- Override `mapItem()` to shape the returned array (always include `'id'` and `'Model'`)
- Add domain-specific finders as plain methods (e.g. `getByEmail`, `getByTenant`)
- No interfaces, no `Contracts/`, no `Eloquent/` subfolder

Folder layout for this project:

```
app/Repositories/
├── AbstractRepo.php                   # already exists
├── User/                              # already exists
│   ├── UserRepo.php
│   ├── RoleRepo.php
│   ├── RightRepo.php
│   └── ManagerProfileRepo.php
├── Ticket/
│   ├── TicketRepo.php
│   ├── ConversationRepo.php
│   └── TimeEntryRepo.php
├── People/
│   ├── ContactRepo.php
│   ├── CompanyRepo.php
│   └── AgentRepo.php
├── Access/
│   └── GroupRepo.php
└── Config/
    ├── SettingRepo.php
    └── TicketFieldRepo.php
```

Skeleton (matches `UserRepo`):
```php
namespace App\Repositories\Ticket;

use App\Models\Ticket;
use App\Repositories\AbstractRepo;

class TicketRepo extends AbstractRepo
{
    protected $withRelations = ['requester', 'responder', 'group'];

    public function __construct()
    {
        $this->model = new Ticket();
    }

    public function getByFreshdeskId(int $fdId)
    {
        $item = $this->model
            ->where('freshdesk_id', $fdId)
            ->with($this->withRelations)
            ->first();

        return $this->mapItem($item);
    }

    public function mapItem($item)
    {
        if (empty($item)) {
            return null;
        }

        return [
            'id' => $item->id,
            'freshdesk_id' => $item->freshdesk_id,
            'subject' => $item->subject,
            'status' => $item->status,
            'priority' => $item->priority,
            'Model' => $item,
        ];
    }
}
```

### RepositoriesProvider

New provider: `app/Providers/RepositoriesProvider.php`, registered in [bootstrap/providers.php](../bootstrap/providers.php). Binds each concrete repo as a singleton so it can be constructor-injected into Actions/Controllers:

```php
public function register(): void
{
    foreach ([
        \App\Repositories\User\UserRepo::class,
        \App\Repositories\User\RoleRepo::class,
        \App\Repositories\User\RightRepo::class,
        \App\Repositories\User\ManagerProfileRepo::class,
        \App\Repositories\Ticket\TicketRepo::class,
        \App\Repositories\Ticket\ConversationRepo::class,
        \App\Repositories\Ticket\TimeEntryRepo::class,
        \App\Repositories\People\ContactRepo::class,
        \App\Repositories\People\CompanyRepo::class,
        \App\Repositories\People\AgentRepo::class,
        \App\Repositories\Access\GroupRepo::class,
        \App\Repositories\Config\SettingRepo::class,
        \App\Repositories\Config\TicketFieldRepo::class,
    ] as $repo) {
        $this->app->singleton($repo);
    }
}
```

---

## 2. Routes — three surfaces, versioned

**The entire site is a Vue 3 SPA authenticated via Laravel Sanctum (cookie-based SPA auth).** Laravel serves JSON only; `/dashboard` and `/portal` are both rendered by Vue in the browser. No Inertia, no server-rendered app pages.

Three route files under [routes/](../routes/), registered in [bootstrap/app.php](../bootstrap/app.php) via `withRouting()`:

```
routes/
├── web.php            # SPA shell (catch-all) + Sanctum CSRF cookie + signed downloads
├── api/
│   └── v1.php         # all app functionality as JSON, Sanctum SPA auth, prefix /api/v1
└── rest/
    └── v1.php         # inbound webhooks (Freshdesk, Stripe, etc.), prefix /rest/v1
```

`bootstrap/app.php`:
```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api/v1.php',
    apiPrefix: 'api/v1',
    commands: __DIR__.'/../routes/console.php',
    then: function () {
        Route::middleware('rest')
            ->prefix('rest/v1')
            ->group(base_path('routes/rest/v1.php'));
    },
)
```

Versioning rule: **both `api/` and `rest/` are versioned from day one.** Adding `v2` means a new file + route group, never editing `v1` in place. `web.php` is not versioned.

### 2.1 What each surface holds

**`web.php` — shell-only.** Three things live here:
1. `GET /sanctum/csrf-cookie` — auto-registered by Sanctum, issues the XSRF cookie before the SPA's first write request.
2. SPA shell catch-alls — one per app entry:
   - `GET /dashboard/{any?}` → returns `resources/views/apps/dashboard.blade.php` (loads `resources/js/apps/dashboard/main.ts`)
   - `GET /portal/{any?}` → returns `resources/views/apps/portal.blade.php` (loads `resources/js/apps/portal/main.ts`)
   - `GET /` → server-side redirect to `/dashboard` or `/portal` based on role (or `/portal/login` when guest)
3. `GET /downloads/{signed}` — signed temp URLs for file downloads (CSV exports). The API returns a signed URL; the browser follows it here to receive `Content-Disposition`.

Nothing else: no HTML forms, no controllers rendering app pages, no CSRF-guarded POST endpoints on `web`. Deep links from emails (magic link, email verify, password reset) point to SPA URLs (`/portal/reset?token=…`); the SPA reads the token and POSTs it to `/api/v1/portal/auth/*`.

**`api/v1.php` — the app.** Everything the SPA does: auth (login/register/magic-link/verify/reset), admin dashboard endpoints, portal endpoints, reports, system settings, sync triggers. Two namespaces: `/api/v1/admin/*` (superadmin + manager) and `/api/v1/portal/*` (customer).

**`rest/v1.php` — external callers.** Freshdesk webhooks, health checks. No SPA involvement.

### 2.2 Auth model — Sanctum SPA

- Same-origin: SPA and API served from the same domain/subdomain. `SANCTUM_STATEFUL_DOMAINS` is set to the app host.
- First paint: SPA calls `GET /sanctum/csrf-cookie` before any write.
- Login: `POST /api/v1/(admin|portal)/auth/login` with credentials → server sets `laravel_session` + `XSRF-TOKEN` cookies → subsequent requests authenticated by cookie.
- CSRF: Axios/Fetch reads `XSRF-TOKEN` cookie and sends `X-XSRF-TOKEN` header on every write.
- Logout: `POST /api/v1/(admin|portal)/auth/logout` invalidates the session.
- Programmatic clients (external integrations, future mobile): use personal access tokens from `/dashboard/system/api-keys`, sent as `Authorization: Bearer <token>` against `/api/v1/*`. Same middleware stack, different guard (`auth:sanctum` with token abilities mapped from `api_keys.scopes`).

### 2.3 Middleware groups

Defined in `bootstrap/app.php`:

- `web` — minimal: `StartSession`, `ShareErrorsFromSession`, `VerifyCsrfToken`, `SubstituteBindings`. Used only for the shell + download routes.
- `api` — `EnsureFrontendRequestsAreStateful` (Sanctum), `ThrottleRequests:api`, `SubstituteBindings`, JSON error handler. Applied to every `/api/v1/*` route.
- `rest` — webhook signature verification, raw body passthrough, no CSRF, no session.

Aliases: `role:<slug>|<slug>`, `right:<slug>`, `manager.scope`.

### 2.4 Controller namespaces

```
app/Http/Controllers/
├── Web/                       # SPA shell + downloads only (small)
│   ├── SpaShellController.php
│   └── DownloadController.php
│
├── Api/
│   └── V1/
│       ├── Auth/              # session endpoints (login/logout/me, magic link, verify, reset)
│       ├── Admin/             # /api/v1/admin/*
│       │   ├── Tickets/
│       │   ├── People/
│       │   ├── Reports/
│       │   └── System/
│       └── Portal/            # /api/v1/portal/*
│           ├── Auth/
│           ├── Requests/
│           └── Profile/
│
└── Rest/
    └── V1/
        ├── HealthController.php
        └── Webhooks/
            └── FreshdeskController.php
```

### 2.5 Frontend layout

```
resources/
├── views/
│   └── apps/
│       ├── dashboard.blade.php      # <div id="app"></div> + Vite tag for dashboard entry
│       └── portal.blade.php         # same, portal entry
└── js/
    ├── shared/                       # HTTP client (axios w/ XSRF), auth store, UI kit
    │   ├── http.ts
    │   ├── auth.ts
    │   └── components/
    └── apps/
        ├── dashboard/
        │   ├── main.ts
        │   ├── router.ts
        │   ├── pages/
        │   └── stores/
        └── portal/
            ├── main.ts
            ├── router.ts
            ├── pages/
            └── stores/
```

Vite is configured with two entries (`resources/js/apps/dashboard/main.ts`, `resources/js/apps/portal/main.ts`) so each Blade shell loads only its app's bundle.

---

## 3. Controllers are thin — logic lives in Actions

Location: [app/Actions/](../app/Actions/)

Rule: **A controller method = request validation + call one Action + return response.** No business rules, no DB queries, no Freshdesk calls in controllers.

Action shape — single public `handle()` (or `__invoke()`), constructor-injected dependencies, returns a DTO or throws a domain exception.

Folder layout (grouped by domain, sub-grouped by operation):

```
app/Actions/
├── Tickets/
│   ├── CreateTicketAction.php
│   ├── UpdateTicketAction.php
│   ├── BulkUpdateTicketsAction.php
│   ├── MergeTicketsAction.php
│   ├── ForwardTicketAction.php
│   └── Conversations/
│       ├── PostReplyAction.php
│       └── PostNoteAction.php
│
├── People/
│   ├── Contacts/
│   │   ├── CreateContactAction.php
│   │   ├── UpdateContactAction.php
│   │   ├── MergeContactsAction.php
│   │   └── SendInviteAction.php
│   ├── Companies/
│   │   ├── CreateCompanyAction.php
│   │   └── UpdateCompanyAction.php
│   └── Agents/
│       ├── CreateAgentAction.php
│       └── UpdateAgentAction.php
│
├── Portal/
│   ├── SubmitTicketAction.php
│   ├── ReplyToTicketAction.php
│   └── RateTicketAction.php
│
├── Sync/                              # Freshdesk ↔ local mirror
│   ├── SyncTicketsAction.php
│   ├── SyncContactsAction.php
│   ├── SyncCompaniesAction.php
│   └── SyncAgentsAction.php
│
├── Reports/
│   ├── BacklogReportAction.php
│   ├── AgentPerformanceReportAction.php
│   └── SlaBreachReportAction.php
│
├── Settings/
│   ├── UpdateFreshdeskConnectionAction.php
│   └── RotateApiKeyAction.php
│
└── Webhooks/
    └── Freshdesk/
        ├── HandleTicketCreatedAction.php
        └── HandleTicketUpdatedAction.php
```

Example flow:

```php
// Controller — thin
final class TicketController extends Controller
{
    public function store(StoreTicketRequest $request, CreateTicketAction $action): JsonResponse
    {
        $ticket = $action->handle($request->toDto());
        return TicketResource::make($ticket)->response()->setStatusCode(201);
    }
}

// Action — orchestrates repo + external client
final class CreateTicketAction
{
    public function __construct(
        private TicketRepo $tickets,
        private FreshdeskClient $freshdesk,
    ) {}

    public function handle(array $data): array
    {
        $remote = $this->freshdesk->tickets()->create($data);
        return $this->tickets->create([
            'freshdesk_id' => $remote['id'],
            'subject'      => $remote['subject'],
            'status'       => $remote['status'],
            'priority'     => $remote['priority'],
            // ...map the rest
        ]);
    }
}
```

---

## 4. Services

Location: [app/Services/](../app/Services/)

Rule: **cross-cutting business logic that doesn't belong to a single Action, Repo, or integration goes into a Service.** Services are stateless, constructor-injected, and wired through a dedicated provider. They encapsulate OOP responsibilities that would otherwise leak into Actions.

Each integration (Freshdesk, future Stripe, etc.) has **one façade Service** under `app/Services/` whose implementation lives under `app/mixins/integrations/{name}/` (see [05-freshdesk-integration.md](05-freshdesk-integration.md)).

Conventions:
- One responsibility per class; name ends in `Service` (e.g. `FreshdeskService`, `AuditService`, `MailerService`).
- Constructor-injected dependencies only — no facades inside Services.
- Methods return DTOs, arrays, or throw domain exceptions. Never return Eloquent models.
- Stateless — no per-request state; safe to resolve as singletons.
- When a Service needs DB access, it goes through a Repo.
- When it needs external HTTP, it goes through an integration in `app/mixins/integrations/`.

Expected initial services:

```
app/Services/
├── FreshdeskService.php       # façade for app/mixins/integrations/freshdesk/
├── AuditService.php           # writes entries into audit_log; used by Actions
├── MailerService.php          # templated emails (magic link, verify, reset, invite)
├── ReverbBroadcastService.php # wraps Reverb channel broadcasts for consistent event shape
├── FileStorageService.php     # upload/fetch/sign for attachments + exports
├── SettingsService.php        # typed getters/setters over SettingRepo with encrypt/decrypt
├── SignedUrlService.php       # issues/verifies the /downloads/{signed} links
└── RateLimitService.php       # our own app-level throttling helpers (not Freshdesk)
```

### ServicesProvider

New provider: `app/Providers/ServicesProvider.php`, registered in [bootstrap/providers.php](../bootstrap/providers.php) alongside `RepositoriesProvider`. Binds each Service as a singleton so it can be constructor-injected anywhere (Actions, controllers, jobs, other services):

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ServicesProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            \App\Services\FreshdeskService::class,
            \App\Services\AuditService::class,
            \App\Services\MailerService::class,
            \App\Services\ReverbBroadcastService::class,
            \App\Services\FileStorageService::class,
            \App\Services\SettingsService::class,
            \App\Services\SignedUrlService::class,
            \App\Services\RateLimitService::class,
        ] as $service) {
            $this->app->singleton($service);
        }
    }
}
```

Usage:

```php
final class CreateTicketAction
{
    public function __construct(
        private TicketRepo $tickets,
        private FreshdeskService $freshdesk,
        private AuditService $audit,
        private ReverbBroadcastService $broadcast,
    ) {}

    public function handle(array $data): array
    {
        $remote = $this->freshdesk->tickets()->create($data);
        $row    = $this->tickets->upsertFromFreshdesk($remote);
        $this->audit->record('tickets.create', target: $row);
        $this->broadcast->toAdmin('tickets', ['event' => 'created', 'ticket' => $row]);
        return $row;
    }
}
```

Adding a new Service = create the class + add one line to `ServicesProvider::register()`.

---

## 5. Operational rules (always follow)

### 5.1 Artisan commands run in Docker

This project ships with a `workspace` container defined in [compose.dev.yaml](../compose.dev.yaml). **Never run `php artisan` on the host** — always go through the container so PHP version, extensions, and mounted paths match production:

```bash
docker compose -f compose.dev.yaml exec workspace php artisan <command>
```

Examples:

```bash
docker compose -f compose.dev.yaml exec workspace php artisan migrate:fresh --seed
docker compose -f compose.dev.yaml exec workspace php artisan make:controller Api/V1/Admin/TicketController
docker compose -f compose.dev.yaml exec workspace php artisan test
docker compose -f compose.dev.yaml exec workspace php artisan horizon:terminate
docker compose -f compose.dev.yaml exec workspace composer dump-autoload
```

Applies to `composer`, `npm`, `vendor/bin/*` too — all run inside `workspace`.

### 5.2 Rebuild the frontend after every change

Vite's dev server is not always running in this workflow. After **any** change under `resources/js/` or `resources/css/`, rebuild the production bundle so the served app reflects the change:

```bash
docker compose -f compose.dev.yaml exec workspace npm run build
```

For iterative work, `npm run dev` (also run inside the container) is fine; but **before committing, always run `npm run build` once** so the committed manifest matches the source.

Rule of thumb:
- Changed `resources/js/apps/dashboard/**` or `resources/js/apps/portal/**` → rebuild.
- Changed a `.blade.php` shell → no rebuild needed.
- Changed `tailwind.config.js` / `vite.config.js` → rebuild.
- Changed a Vue component used in tests → rebuild then run `npm run test:unit`.

---

## Surrounding pieces (to be detailed in other docs)

- `app/Mixins/Integrations/{name}/` — integration internals (HTTP clients, rate limiters, resource classes, DTOs). See [05-freshdesk-integration.md](05-freshdesk-integration.md).
- `app/DTOs/` — input/output objects passed between Actions and Repositories
- `app/Http/Requests/` — Form Requests per route surface (`Api/V1/`, `Rest/V1/`)
- `app/Http/Resources/` — API response shapers (for `api/v1` only)
- `app/Jobs/` — Horizon jobs for sync + heavy bulk operations
- `app/Events/` + Reverb channels — realtime dashboard updates
