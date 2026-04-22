# 01 — Data Layer

Foundation step. Creates every table the app needs in the initial version, the models that wrap them, the repositories that own all access, and the seed data that makes the app immediately usable. No later section touches the DB without going through what's defined here.

## Overview

17 tables, grouped:

| Group | Tables |
|---|---|
| **Auth & access** | `users`, `roles`, `user_roles`, `role_rights` |
| **Freshdesk mirror** | `companies`, `contacts`, `agents`, `groups`, `tickets`, `conversations`, `time_entries` |
| **App-internal** | `manager_group_scopes`, `settings`, `sync_jobs`, `audit_log`, `api_keys`, `portal_drafts` |

The permission slug enumeration is in [rights-catalog.md](rights-catalog.md).

## Mirror convention (Freshdesk tables)

Every mirrored table stores:

- a local surrogate `id`
- a unique `freshdesk_id` (bigint, indexed) — the Freshdesk resource id
- **promoted columns** for anything we query/filter/sort on (status, priority, email, company_id…)
- a `payload` JSON column — the complete original response body, fallback for any field not promoted
- `fd_created_at`, `fd_updated_at` — timestamps from Freshdesk
- `synced_at` — local timestamp of the last successful upsert (used by incremental pollers)

Write path for all mutations: `Action → FreshdeskClient → upsert into mirror table`. Reads hit the mirror.

---

## 1. Migrations

Listed in execution order. Keep the timestamp prefixes monotonically increasing.

### 1.1 `users` — edit [0001_01_01_000000_create_users_table.php](../database/migrations/0001_01_01_000000_create_users_table.php)

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->string('phone')->nullable();
    $table->string('avatar')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_login_at')->nullable();
    $table->unsignedBigInteger('freshdesk_contact_id')->nullable()->index(); // portal users only
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
});
// password_reset_tokens + sessions stay as Laravel's default.
```

### 1.2 `roles`

```php
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();     // superadmin | manager | customer
    $table->string('name');
    $table->string('description')->nullable();
    $table->boolean('is_system')->default(true);
    $table->timestamps();
});
```

### 1.3 `user_roles` (pivot)

```php
Schema::create('user_roles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('role_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['user_id', 'role_id']);
});
```

### 1.4 `role_rights`

```php
Schema::create('role_rights', function (Blueprint $table) {
    $table->id();
    $table->foreignId('role_id')->constrained()->cascadeOnDelete();
    $table->string('right', 100);        // slug from App\Support\Rights
    $table->string('group', 60);         // e.g. tickets, system
    $table->timestamps();
    $table->unique(['role_id', 'right']);
    $table->index(['group']);
});
```

### 1.5 `companies` (mirror)

```php
Schema::create('companies', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('freshdesk_id')->unique();
    $table->string('name')->index();
    $table->text('description')->nullable();
    $table->json('domains')->nullable();
    $table->text('note')->nullable();
    $table->string('health_score')->nullable();
    $table->string('account_tier')->nullable();
    $table->date('renewal_date')->nullable();
    $table->string('industry')->nullable();
    $table->json('custom_fields')->nullable();
    $table->json('payload')->nullable();
    $table->timestamp('fd_created_at')->nullable();
    $table->timestamp('fd_updated_at')->nullable()->index();
    $table->timestamp('synced_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

### 1.6 `contacts` (mirror)

```php
Schema::create('contacts', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('freshdesk_id')->unique();
    $table->string('name');
    $table->string('email')->nullable()->index();
    $table->string('phone')->nullable();
    $table->string('mobile')->nullable();
    $table->string('twitter_id')->nullable();
    $table->string('unique_external_id')->nullable()->index();
    $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
    $table->unsignedBigInteger('freshdesk_company_id')->nullable()->index();
    $table->string('job_title')->nullable();
    $table->string('language', 10)->nullable();
    $table->string('time_zone')->nullable();
    $table->text('address')->nullable();
    $table->boolean('active')->default(false);
    $table->boolean('view_all_tickets')->default(false);
    $table->json('other_emails')->nullable();
    $table->json('other_companies')->nullable();
    $table->json('tags')->nullable();
    $table->json('custom_fields')->nullable();
    $table->json('payload')->nullable();
    $table->timestamp('fd_created_at')->nullable();
    $table->timestamp('fd_updated_at')->nullable()->index();
    $table->timestamp('synced_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

### 1.7 `agents` (mirror — Freshdesk agents, distinct from our `users`)

```php
Schema::create('agents', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('freshdesk_id')->unique();
    $table->string('email')->index();
    $table->string('name')->nullable();
    $table->string('job_title')->nullable();
    $table->string('language', 10)->nullable();
    $table->string('time_zone')->nullable();
    $table->boolean('available')->default(false);
    $table->boolean('occasional')->default(false);
    $table->string('type', 30)->nullable();          // support_agent | field_agent | collaborator
    $table->unsignedTinyInteger('ticket_scope')->nullable();   // 1 Global | 2 Group | 3 Restricted
    $table->text('signature')->nullable();
    $table->json('group_ids')->nullable();
    $table->json('role_ids')->nullable();
    $table->json('skill_ids')->nullable();
    $table->json('payload')->nullable();
    $table->timestamp('fd_created_at')->nullable();
    $table->timestamp('fd_updated_at')->nullable()->index();
    $table->timestamp('synced_at')->nullable();
    $table->timestamps();
});
```

### 1.8 `groups` (mirror)

```php
Schema::create('groups', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('freshdesk_id')->unique();
    $table->string('name');
    $table->text('description')->nullable();
    $table->string('unassigned_for', 10)->nullable();
    $table->unsignedBigInteger('business_hour_id')->nullable();
    $table->unsignedBigInteger('escalate_to')->nullable();
    $table->json('agent_ids')->nullable();
    $table->boolean('auto_ticket_assign')->default(false);
    $table->json('payload')->nullable();
    $table->timestamp('fd_created_at')->nullable();
    $table->timestamp('fd_updated_at')->nullable()->index();
    $table->timestamp('synced_at')->nullable();
    $table->timestamps();
});
```

### 1.9 `tickets` (mirror)

```php
Schema::create('tickets', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('freshdesk_id')->unique();
    $table->string('subject');
    $table->longText('description')->nullable();
    $table->longText('description_text')->nullable();
    $table->unsignedTinyInteger('status')->index();       // 2..5
    $table->unsignedTinyInteger('priority')->index();     // 1..4
    $table->unsignedTinyInteger('source')->nullable();
    $table->string('type')->nullable();

    $table->foreignId('requester_id')->nullable()->constrained('contacts')->nullOnDelete();
    $table->unsignedBigInteger('freshdesk_requester_id')->nullable()->index();
    $table->foreignId('responder_id')->nullable()->constrained('agents')->nullOnDelete();
    $table->unsignedBigInteger('freshdesk_responder_id')->nullable()->index();
    $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
    $table->unsignedBigInteger('freshdesk_group_id')->nullable()->index();
    $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
    $table->unsignedBigInteger('freshdesk_company_id')->nullable()->index();
    $table->unsignedBigInteger('product_id')->nullable();
    $table->unsignedBigInteger('email_config_id')->nullable();
    $table->unsignedBigInteger('parent_id')->nullable()->index();

    $table->boolean('spam')->default(false);
    $table->boolean('is_escalated')->default(false);
    $table->boolean('fr_escalated')->default(false);

    $table->timestamp('due_by')->nullable()->index();
    $table->timestamp('fr_due_by')->nullable();

    $table->json('to_emails')->nullable();
    $table->json('cc_emails')->nullable();
    $table->json('fwd_emails')->nullable();
    $table->json('reply_cc_emails')->nullable();
    $table->json('tags')->nullable();
    $table->json('custom_fields')->nullable();
    $table->json('payload')->nullable();

    $table->timestamp('fd_created_at')->nullable()->index();
    $table->timestamp('fd_updated_at')->nullable()->index();
    $table->timestamp('synced_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

### 1.10 `conversations` (mirror — replies & notes)

```php
Schema::create('conversations', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('freshdesk_id')->unique();
    $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
    $table->unsignedBigInteger('freshdesk_ticket_id')->index();
    $table->unsignedBigInteger('user_id')->nullable();      // Freshdesk user_id (agent or contact)
    $table->longText('body')->nullable();
    $table->longText('body_text')->nullable();
    $table->boolean('private')->default(false);             // true = internal note
    $table->boolean('incoming')->default(false);            // true = from customer
    $table->unsignedTinyInteger('source')->nullable();
    $table->string('from_email')->nullable();
    $table->json('to_emails')->nullable();
    $table->json('cc_emails')->nullable();
    $table->json('bcc_emails')->nullable();
    $table->json('attachments')->nullable();
    $table->json('payload')->nullable();
    $table->timestamp('fd_created_at')->nullable()->index();
    $table->timestamp('fd_updated_at')->nullable();
    $table->timestamp('synced_at')->nullable();
    $table->timestamps();
});
```

### 1.11 `time_entries` (mirror)

```php
Schema::create('time_entries', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('freshdesk_id')->unique();
    $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
    $table->unsignedBigInteger('freshdesk_ticket_id')->index();
    $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
    $table->unsignedBigInteger('freshdesk_agent_id')->nullable()->index();
    $table->string('time_spent', 10)->nullable();           // HH:MM
    $table->text('note')->nullable();
    $table->boolean('billable')->default(false);
    $table->boolean('timer_running')->default(false);
    $table->timestamp('executed_at')->nullable();
    $table->timestamp('start_time')->nullable();
    $table->json('payload')->nullable();
    $table->timestamp('fd_created_at')->nullable();
    $table->timestamp('fd_updated_at')->nullable();
    $table->timestamp('synced_at')->nullable();
    $table->timestamps();
});
```

### 1.12 `manager_group_scopes` (manager ↔ group)

```php
Schema::create('manager_group_scopes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['user_id', 'group_id']);
});
```

### 1.13 `settings` (admin-editable key/value)

```php
Schema::create('settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->longText('value')->nullable();            // encrypted when type = 'encrypted'
    $table->string('type', 20)->default('string');    // string | int | bool | json | encrypted
    $table->string('group', 60)->default('general');  // general | freshdesk | notifications | portal
    $table->string('description')->nullable();
    $table->timestamps();
});
```

### 1.14 `sync_jobs` (log of sync runs)

```php
Schema::create('sync_jobs', function (Blueprint $table) {
    $table->id();
    $table->string('resource', 40)->index();          // tickets | contacts | companies | agents | groups | conversations | time_entries
    $table->string('mode', 20)->default('incremental');// incremental | full
    $table->string('status', 20)->default('running'); // running | success | failed
    $table->timestamp('started_at')->nullable();
    $table->timestamp('finished_at')->nullable();
    $table->unsignedInteger('items_processed')->default(0);
    $table->unsignedInteger('items_upserted')->default(0);
    $table->unsignedInteger('items_failed')->default(0);
    $table->text('error')->nullable();
    $table->json('meta')->nullable();
    $table->timestamps();
});
```

### 1.15 `audit_log`

```php
Schema::create('audit_log', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('actor_type', 20)->default('user');   // user | system | webhook | api
    $table->string('action');                            // e.g. tickets.update
    $table->string('target_type')->nullable();           // e.g. Ticket
    $table->unsignedBigInteger('target_id')->nullable();
    $table->string('source', 20)->default('web');        // web | api | rest | system
    $table->json('payload_before')->nullable();
    $table->json('payload_after')->nullable();
    $table->json('meta')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->timestamps();
    $table->index(['target_type', 'target_id']);
    $table->index(['action', 'created_at']);
});
```

### 1.16 `api_keys`

```php
Schema::create('api_keys', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('prefix', 12)->unique();           // first 8–10 chars of the key, shown in UI
    $table->string('hash', 64)->unique();             // sha256 of the full key
    $table->json('scopes')->nullable();               // array of right slugs
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('last_used_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamp('revoked_at')->nullable();
    $table->timestamps();
});
```

### 1.17 `portal_drafts` (unsent customer request drafts)

```php
Schema::create('portal_drafts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->json('payload');            // subject, description, type, product_id, custom_fields, attachments meta
    $table->timestamps();
});
```

---

## 2. Models

Each model lives in `app/Models/`, uses `$fillable` (or `$guarded = ['id']` for mirror tables), has `$casts` for JSON / datetime / bool, and declares its relations.

| Model | File | Relations |
|---|---|---|
| `User` | `User.php` | `roles()` bt-many → Role (`user_roles`); `managerGroups()` bt-many → Group (`manager_group_scopes`); `freshdeskContact()` belongsTo Contact (`freshdesk_contact_id`→`contacts.freshdesk_id`) |
| `Role` | `Role.php` | `users()` bt-many; `rights()` hasMany RoleRight |
| `RoleRight` | `RoleRight.php` | `role()` belongsTo Role |
| `Company` | `Company.php` | `contacts()` hasMany; `tickets()` hasMany |
| `Contact` | `Contact.php` | `company()` belongsTo; `tickets()` hasMany (requester) |
| `Agent` | `Agent.php` | `tickets()` hasMany (responder); `timeEntries()` hasMany |
| `Group` | `Group.php` | `tickets()` hasMany; `managers()` bt-many User via `manager_group_scopes` |
| `Ticket` | `Ticket.php` | `requester()`, `responder()`, `group()`, `company()`, `conversations()` hasMany, `timeEntries()` hasMany |
| `Conversation` | `Conversation.php` | `ticket()` belongsTo |
| `TimeEntry` | `TimeEntry.php` | `ticket()`, `agent()` belongsTo |
| `ManagerGroupScope` | `ManagerGroupScope.php` | pivot; `user()`, `group()` belongsTo |
| `Setting` | `Setting.php` | — |
| `SyncJob` | `SyncJob.php` | — |
| `AuditLog` | `AuditLog.php` | `user()` belongsTo |
| `ApiKey` | `ApiKey.php` | `creator()` belongsTo User |
| `PortalDraft` | `PortalDraft.php` | `user()` belongsTo |

### 2.1 `User.php`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name','email','password','phone','avatar','is_active',
        'freshdesk_contact_id','last_login_at','email_verified_at',
    ];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at'     => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    public function roles()            { return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps(); }
    public function managerGroups()    { return $this->belongsToMany(Group::class, 'manager_group_scopes')->withTimestamps(); }
    public function freshdeskContact() { return $this->belongsTo(Contact::class, 'freshdesk_contact_id', 'freshdesk_id'); }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function rights(): array
    {
        return $this->roles->flatMap->rights->pluck('right')->unique()->values()->all();
    }

    public function hasRight(string $right): bool
    {
        return in_array($right, $this->rights(), true);
    }
}
```

### 2.2 Mirror model template — `Ticket.php`

All mirror models follow the same shape. Ticket is the fullest example.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'to_emails'       => 'array',
        'cc_emails'       => 'array',
        'fwd_emails'      => 'array',
        'reply_cc_emails' => 'array',
        'tags'            => 'array',
        'custom_fields'   => 'array',
        'payload'         => 'array',
        'spam'            => 'boolean',
        'is_escalated'    => 'boolean',
        'fr_escalated'    => 'boolean',
        'due_by'          => 'datetime',
        'fr_due_by'       => 'datetime',
        'fd_created_at'   => 'datetime',
        'fd_updated_at'   => 'datetime',
        'synced_at'       => 'datetime',
    ];

    public function requester()     { return $this->belongsTo(Contact::class, 'requester_id'); }
    public function responder()     { return $this->belongsTo(Agent::class, 'responder_id'); }
    public function group()         { return $this->belongsTo(Group::class); }
    public function company()       { return $this->belongsTo(Company::class); }
    public function conversations() { return $this->hasMany(Conversation::class); }
    public function timeEntries()   { return $this->hasMany(TimeEntry::class); }
}
```

Other mirror models (`Contact`, `Company`, `Agent`, `Group`, `Conversation`, `TimeEntry`) use the same pattern: `$guarded = ['id']`, cast JSON columns to `array`, cast `fd_created_at`/`fd_updated_at`/`synced_at` + any other timestamp columns to `datetime`, cast boolean flags to `boolean`.

---

## 3. Repositories

Every repo extends [AbstractRepo](../app/Repositories/AbstractRepo.php) and follows the pattern of the existing [UserRepo](../app/Repositories/User/UserRepo.php): constructor sets `$this->model`, `$withRelations` declares eager loads, `mapItem()` shapes the returned array, domain-specific finders are plain public methods.

Layout under `app/Repositories/`:

```
app/Repositories/
├── AbstractRepo.php                  (already exists)
├── User/
│   ├── UserRepo.php                  (exists — tenant logic removed)
│   ├── RoleRepo.php                  (exists — tenant logic removed)
│   └── RoleRightRepo.php             (replaces RightRepo)
├── People/
│   ├── ContactRepo.php
│   ├── CompanyRepo.php
│   └── AgentRepo.php
├── Ticket/
│   ├── TicketRepo.php
│   ├── ConversationRepo.php
│   └── TimeEntryRepo.php
├── Group/
│   ├── GroupRepo.php
│   └── ManagerGroupScopeRepo.php
├── System/
│   ├── SettingRepo.php
│   ├── SyncJobRepo.php
│   ├── AuditLogRepo.php
│   └── ApiKeyRepo.php
└── Portal/
    └── PortalDraftRepo.php
```

### 3.1 Custom methods per repo (beyond AbstractRepo)

| Repo | Methods |
|---|---|
| `UserRepo` | `getByEmail`, `getByRoleSlug`, `getByFreshdeskContactId`, `touchLastLogin`, `syncRoles(int $userId, array $roleIds)` |
| `RoleRepo` | `getBySlug`, `syncRights(int $roleId, array $rightSlugs)` |
| `RoleRightRepo` | `getByRole(int $roleId)`, `getCatalog()` (returns `Rights::catalog()`) |
| `ContactRepo` | `getByFreshdeskId`, `getByEmail`, `getByCompany(int $companyId)`, `upsertFromFreshdesk(array $payload)` |
| `CompanyRepo` | `getByFreshdeskId`, `getByDomain(string $domain)`, `upsertFromFreshdesk` |
| `AgentRepo` | `getByFreshdeskId`, `getByEmail`, `upsertFromFreshdesk` |
| `GroupRepo` | `getByFreshdeskId`, `getByIds(array $ids)`, `upsertFromFreshdesk` |
| `TicketRepo` | `getByFreshdeskId`, `scopedToGroups(array $groupIds)`, `overdue()`, `unassigned()`, `pendingCustomerReply()`, `upsertFromFreshdesk` |
| `ConversationRepo` | `getByTicket(int $ticketId)`, `publicForCustomer(int $ticketId)` (filters `private=false`), `upsertFromFreshdesk` |
| `TimeEntryRepo` | `getByTicket`, `getByAgent`, `upsertFromFreshdesk` |
| `ManagerGroupScopeRepo` | `groupIdsForUser(int $userId): array`, `sync(int $userId, array $groupIds): void` |
| `SettingRepo` | `get(string $key, $default = null)`, `set(string $key, $value, string $type = 'string', string $group = 'general')`, `getGroup(string $group): array` |
| `SyncJobRepo` | `start(string $resource, string $mode)`, `finish(int $jobId, int $processed, int $upserted, int $failed)`, `fail(int $jobId, string $error)`, `lastSuccessful(string $resource)` |
| `AuditLogRepo` | `record(array $entry)`, `paginateForTarget(string $type, int $id)` |
| `ApiKeyRepo` | `createKey(string $name, array $scopes, ?int $createdBy): array` (returns plaintext key once), `findByHash(string $hash)`, `rotate(int $id)`, `revoke(int $id)` |
| `PortalDraftRepo` | `getForUser(int $userId)`, `replaceForUser(int $userId, array $payload)`, `clearForUser(int $userId)` |

All mirror repos share the same `upsertFromFreshdesk(array $payload): array` signature — Actions never format SQL directly.

### 3.2 `RepositoriesProvider`

`app/Providers/RepositoriesProvider.php`, registered in [bootstrap/providers.php](../bootstrap/providers.php):

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoriesProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([
            // User
            \App\Repositories\User\UserRepo::class,
            \App\Repositories\User\RoleRepo::class,
            \App\Repositories\User\RoleRightRepo::class,
            // People
            \App\Repositories\People\ContactRepo::class,
            \App\Repositories\People\CompanyRepo::class,
            \App\Repositories\People\AgentRepo::class,
            // Ticket
            \App\Repositories\Ticket\TicketRepo::class,
            \App\Repositories\Ticket\ConversationRepo::class,
            \App\Repositories\Ticket\TimeEntryRepo::class,
            // Group
            \App\Repositories\Group\GroupRepo::class,
            \App\Repositories\Group\ManagerGroupScopeRepo::class,
            // System
            \App\Repositories\System\SettingRepo::class,
            \App\Repositories\System\SyncJobRepo::class,
            \App\Repositories\System\AuditLogRepo::class,
            \App\Repositories\System\ApiKeyRepo::class,
            // Portal
            \App\Repositories\Portal\PortalDraftRepo::class,
        ] as $repo) {
            $this->app->singleton($repo);
        }
    }
}
```

---

## 4. Seeders

`database/seeders/DatabaseSeeder.php`:

```php
$this->call([
    RolesSeeder::class,
    RoleRightsSeeder::class,
    UsersSeeder::class,
    SettingsSeeder::class,
]);
```

### 4.1 `RolesSeeder`

```php
$roles = [
    ['slug' => 'superadmin', 'name' => 'Superadmin', 'description' => 'Full access to dashboard and system configuration.'],
    ['slug' => 'manager',    'name' => 'Manager',    'description' => 'Dashboard access scoped to assigned groups.'],
    ['slug' => 'customer',   'name' => 'Customer',   'description' => 'Portal-only access for end users.'],
];
foreach ($roles as $r) {
    Role::updateOrCreate(['slug' => $r['slug']], [...$r, 'is_system' => true]);
}
```

### 4.2 `RoleRightsSeeder`

Implements the role→rights map from [rights-catalog.md](rights-catalog.md).

```php
use App\Support\Rights;

$map = [
    'superadmin' => collect(Rights::catalog())
        ->flatten()
        ->reject(fn ($r) => str_starts_with($r, 'portal.'))
        ->values()->all(),

    'manager' => [
        Rights::TICKETS_VIEW, Rights::TICKETS_CREATE, Rights::TICKETS_UPDATE,
        Rights::TICKETS_DELETE, Rights::TICKETS_RESTORE,
        Rights::TICKETS_BULK_UPDATE, Rights::TICKETS_MERGE,
        Rights::TICKETS_FORWARD, Rights::TICKETS_ASSIGN,
        Rights::CONVERSATIONS_REPLY, Rights::CONVERSATIONS_NOTE, Rights::CONVERSATIONS_UPDATE,
        Rights::CONTACTS_VIEW, Rights::CONTACTS_CREATE, Rights::CONTACTS_UPDATE,
        Rights::CONTACTS_DELETE, Rights::CONTACTS_RESTORE,
        Rights::CONTACTS_MERGE, Rights::CONTACTS_SEND_INVITE,
        Rights::COMPANIES_VIEW, Rights::COMPANIES_UPDATE,
        Rights::AGENTS_VIEW,
        Rights::GROUPS_VIEW,
        Rights::ROLES_VIEW,
        Rights::TIME_ENTRIES_VIEW, Rights::TIME_ENTRIES_CREATE, Rights::TIME_ENTRIES_UPDATE,
        Rights::REPORTS_VIEW, Rights::REPORTS_EXPORT,
    ],

    'customer' => [
        Rights::PORTAL_REQUESTS_VIEW_OWN, Rights::PORTAL_REQUESTS_VIEW_COMPANY,
        Rights::PORTAL_REQUESTS_CREATE, Rights::PORTAL_REQUESTS_REPLY,
        Rights::PORTAL_REQUESTS_RESOLVE, Rights::PORTAL_REQUESTS_REOPEN,
        Rights::PORTAL_REQUESTS_RATE, Rights::PORTAL_PROFILE_UPDATE,
    ],
];

foreach ($map as $slug => $rights) {
    $role = Role::where('slug', $slug)->first();
    if (!$role) continue;

    RoleRight::where('role_id', $role->id)->delete();
    RoleRight::insert(
        collect($rights)->unique()->map(fn ($r) => [
            'role_id'    => $role->id,
            'right'      => $r,
            'group'      => explode('.', $r)[0],
            'created_at' => now(),
            'updated_at' => now(),
        ])->all()
    );
}
```

### 4.3 `UsersSeeder`

```php
$users = [
    ['email' => 'admin@example.test',    'name' => 'Super Admin',   'role' => 'superadmin'],
    ['email' => 'manager@example.test',  'name' => 'Team Manager',  'role' => 'manager'],
    ['email' => 'customer@example.test', 'name' => 'Test Customer', 'role' => 'customer'],
];
foreach ($users as $u) {
    $user = User::updateOrCreate(['email' => $u['email']], [
        'name' => $u['name'],
        'password' => Hash::make('password'),
        'is_active' => true,
        'email_verified_at' => now(),
    ]);
    $role = Role::where('slug', $u['role'])->first();
    if ($role) $user->roles()->syncWithoutDetaching([$role->id]);
}
```

### 4.4 `SettingsSeeder`

Defaults so the admin Settings UI has rows to display from day one.

```php
$defaults = [
    // Freshdesk connection (filled in via /dashboard/system/freshdesk)
    ['key' => 'freshdesk.domain',   'value' => '',  'type' => 'string',    'group' => 'freshdesk', 'description' => 'e.g. acme.freshdesk.com'],
    ['key' => 'freshdesk.api_key',  'value' => '',  'type' => 'encrypted', 'group' => 'freshdesk', 'description' => 'API key (encrypted at rest)'],
    ['key' => 'freshdesk.test_ok',  'value' => '0', 'type' => 'bool',      'group' => 'freshdesk', 'description' => 'Last Test connection result'],

    // App
    ['key' => 'app.name',       'value' => 'Freshdesk Manager', 'type' => 'string', 'group' => 'general'],
    ['key' => 'app.default_tz', 'value' => 'UTC',               'type' => 'string', 'group' => 'general'],
    ['key' => 'app.portal_url', 'value' => '',                  'type' => 'string', 'group' => 'general', 'description' => 'Public portal origin'],

    // Portal toggles
    ['key' => 'portal.allow_public_registration', 'value' => '0', 'type' => 'bool', 'group' => 'portal'],
    ['key' => 'portal.require_captcha',           'value' => '0', 'type' => 'bool', 'group' => 'portal'],
    ['key' => 'portal.csat_on_resolve',           'value' => '1', 'type' => 'bool', 'group' => 'portal'],

    // Sync intervals (minutes)
    ['key' => 'sync.tickets_interval',   'value' => '2',  'type' => 'int', 'group' => 'sync'],
    ['key' => 'sync.contacts_interval',  'value' => '10', 'type' => 'int', 'group' => 'sync'],
    ['key' => 'sync.companies_interval', 'value' => '30', 'type' => 'int', 'group' => 'sync'],
    ['key' => 'sync.agents_interval',    'value' => '30', 'type' => 'int', 'group' => 'sync'],
    ['key' => 'sync.groups_interval',    'value' => '60', 'type' => 'int', 'group' => 'sync'],

    // Notifications
    ['key' => 'notify.slack_webhook', 'value' => '', 'type' => 'encrypted', 'group' => 'notifications'],
    ['key' => 'notify.daily_digest',  'value' => '0','type' => 'bool',       'group' => 'notifications'],
];
foreach ($defaults as $d) {
    Setting::updateOrCreate(['key' => $d['key']], $d);
}
```

---

## 5. Run order

```bash
php artisan migrate:fresh
php artisan db:seed
```

Result:
- All 17 tables created with indexes and foreign keys
- 3 roles (`superadmin`, `manager`, `customer`) with rights assigned per the map in [rights-catalog.md](rights-catalog.md)
- 3 login-ready users (`admin@example.test`, `manager@example.test`, `customer@example.test`, password `password`)
- Default settings rows for Freshdesk connection, sync intervals, portal toggles

---

## 6. Out of scope for this section

Wired up in later sections, not here:

- Middleware (`role:`, `right:`) and policy classes
- Freshdesk HTTP client, rate-limit guard, retry policy
- Sync jobs (Horizon) that populate the mirror tables
- Controllers, Actions, FormRequests, Inertia pages
- Portal auth flow (magic link, verification emails)
- Webhook receivers under `/rest/v1/webhooks/*`

This section only defines **what the DB holds** and **how code reads/writes it**.
