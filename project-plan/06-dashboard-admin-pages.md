# 06 — Dashboard Pages (Admin SPA)

Vue 3 SPA mounted from `resources/js/apps/dashboard/main.ts`, routed under `/dashboard/*`. Covers every screen a **superadmin** uses. A subset of these same pages is reused by managers (see [07-dashboard-manager-pages.md](07-dashboard-manager-pages.md)) — this file is the authoritative description; the manager file calls out the deltas.

All data comes from `/api/v1/admin/*` (Sanctum SPA cookie auth). The Blade shell (`resources/views/apps/dashboard.blade.php`) is the only thing Laravel renders.

---

## 0. SPA layout

```
resources/js/apps/dashboard/
├── main.ts
├── router.ts
├── App.vue
├── layouts/
│   ├── PublicLayout.vue          # centered card — auth pages
│   └── AppLayout.vue             # sidebar + top bar + <RouterView/>
├── pages/
│   ├── auth/
│   │   ├── LoginPage.vue
│   │   ├── ForgotPasswordPage.vue
│   │   └── ResetPasswordPage.vue
│   ├── OverviewPage.vue
│   ├── tickets/
│   │   ├── TicketListPage.vue
│   │   ├── NewTicketPage.vue
│   │   └── TicketDetailPage.vue
│   ├── contacts/
│   │   ├── ContactListPage.vue
│   │   ├── NewContactPage.vue
│   │   └── ContactDetailPage.vue
│   ├── companies/
│   │   ├── CompanyListPage.vue
│   │   ├── NewCompanyPage.vue
│   │   └── CompanyDetailPage.vue
│   ├── agents/
│   │   ├── AgentListPage.vue
│   │   ├── NewAgentPage.vue
│   │   └── AgentDetailPage.vue
│   ├── groups/
│   │   └── GroupListPage.vue
│   ├── reports/
│   │   ├── BacklogReportPage.vue
│   │   ├── AgentPerformancePage.vue
│   │   ├── GroupPerformancePage.vue
│   │   ├── SlaBreachReportPage.vue
│   │   ├── VolumeReportPage.vue
│   │   └── CsatReportPage.vue
│   ├── AuditLogPage.vue
│   ├── system/
│   │   ├── FreshdeskConnectionPage.vue
│   │   ├── ManagersPage.vue
│   │   ├── SyncJobsPage.vue
│   │   ├── SettingsPage.vue
│   │   └── ApiKeysPage.vue
│   └── ProfilePage.vue
├── components/
│   ├── Sidebar.vue
│   ├── TopBar.vue
│   ├── DataTable.vue             # shared table w/ sort/filter/bulk select
│   ├── FilterBar.vue
│   ├── BulkActionsBar.vue
│   ├── SavedViewsPicker.vue
│   ├── StatusPill.vue
│   ├── PriorityIcon.vue
│   ├── AgentAvatar.vue
│   ├── ConfirmModal.vue
│   ├── RichEditor.vue
│   ├── AttachmentDropzone.vue
│   ├── AssignPicker.vue
│   ├── TagInput.vue
│   ├── DateRangePicker.vue
│   └── KpiCard.vue
└── stores/
    ├── auth.ts
    ├── tickets.ts
    ├── contacts.ts
    ├── companies.ts
    ├── agents.ts
    ├── groups.ts
    ├── reports.ts
    ├── audit.ts
    ├── system.ts                  # freshdesk conn, sync jobs, settings, api keys
    ├── managers.ts
    ├── config.ts                  # ticket fields, products, sla policies, business hours, automations
    └── ui.ts                      # toasts, modals, confirm prompts
```

---

## 1. Router

```ts
const routes = [
  // Public
  { path: '/dashboard/login',     component: LoginPage,          meta: { layout: 'public', guest: true } },
  { path: '/dashboard/forgot',    component: ForgotPasswordPage, meta: { layout: 'public', guest: true } },
  { path: '/dashboard/reset',     component: ResetPasswordPage,  meta: { layout: 'public', guest: true } },

  // Authenticated
  { path: '/dashboard',                      component: OverviewPage,         meta: { auth: true } },
  { path: '/dashboard/tickets',              component: TicketListPage,       meta: { auth: true, right: 'tickets.view' } },
  { path: '/dashboard/tickets/new',          component: NewTicketPage,        meta: { auth: true, right: 'tickets.create' } },
  { path: '/dashboard/tickets/:id',          component: TicketDetailPage,     meta: { auth: true, right: 'tickets.view' }, props: true },
  { path: '/dashboard/contacts',             component: ContactListPage,      meta: { auth: true, right: 'contacts.view' } },
  { path: '/dashboard/contacts/new',         component: NewContactPage,       meta: { auth: true, right: 'contacts.create' } },
  { path: '/dashboard/contacts/:id',         component: ContactDetailPage,    meta: { auth: true, right: 'contacts.view' }, props: true },
  { path: '/dashboard/companies',            component: CompanyListPage,      meta: { auth: true, right: 'companies.view' } },
  { path: '/dashboard/companies/new',        component: NewCompanyPage,       meta: { auth: true, right: 'companies.create' } },
  { path: '/dashboard/companies/:id',        component: CompanyDetailPage,    meta: { auth: true, right: 'companies.view' }, props: true },
  { path: '/dashboard/agents',               component: AgentListPage,        meta: { auth: true, right: 'agents.view' } },
  { path: '/dashboard/agents/new',           component: NewAgentPage,         meta: { auth: true, right: 'agents.create' } },
  { path: '/dashboard/agents/:id',           component: AgentDetailPage,      meta: { auth: true, right: 'agents.view' }, props: true },
  { path: '/dashboard/groups',               component: GroupListPage,        meta: { auth: true, right: 'groups.view' } },
  { path: '/dashboard/reports/backlog',           component: BacklogReportPage,      meta: { auth: true, right: 'reports.view' } },
  { path: '/dashboard/reports/agent-performance', component: AgentPerformancePage,   meta: { auth: true, right: 'reports.view' } },
  { path: '/dashboard/reports/group-performance', component: GroupPerformancePage,   meta: { auth: true, right: 'reports.view' } },
  { path: '/dashboard/reports/sla-breaches',      component: SlaBreachReportPage,    meta: { auth: true, right: 'reports.view' } },
  { path: '/dashboard/reports/volume',            component: VolumeReportPage,       meta: { auth: true, right: 'reports.view' } },
  { path: '/dashboard/reports/csat',              component: CsatReportPage,         meta: { auth: true, right: 'reports.view' } },
  { path: '/dashboard/audit-log',            component: AuditLogPage,         meta: { auth: true, right: 'audit.view' } },

  // System (superadmin only)
  { path: '/dashboard/system/freshdesk',   component: FreshdeskConnectionPage, meta: { auth: true, role: 'superadmin', right: 'system.freshdesk.view' } },
  { path: '/dashboard/system/managers',    component: ManagersPage,            meta: { auth: true, role: 'superadmin', right: 'system.managers.view' } },
  { path: '/dashboard/system/sync-jobs',   component: SyncJobsPage,            meta: { auth: true, role: 'superadmin', right: 'system.sync_jobs.view' } },
  { path: '/dashboard/system/settings',    component: SettingsPage,            meta: { auth: true, role: 'superadmin', right: 'system.settings.view' } },
  { path: '/dashboard/system/api-keys',    component: ApiKeysPage,             meta: { auth: true, role: 'superadmin', right: 'system.api_keys.view' } },

  { path: '/dashboard/profile',            component: ProfilePage,             meta: { auth: true } },
  { path: '/dashboard/:pathMatch(.*)*',    redirect: '/dashboard' },
];
```

**Global guard:** on every nav, ensure `auth.user` is loaded (`GET /api/v1/admin/auth/me`), then check `meta.auth`, `meta.role`, `meta.right` against the user. Mismatch → toast + redirect to `/dashboard` (or 403 page if the current route itself is forbidden post-login).

---

## 2. Shell — Sidebar + TopBar

**Sidebar (`Sidebar.vue`):** conditional menu rendering from `auth.user` rights. Superadmin sees everything; hidden nodes for manager are handled in [07](07-dashboard-manager-pages.md).

Menu items:
- Overview
- Tickets
- Contacts
- Companies
- Agents
- Groups
- Reports (collapsible — Backlog, Agent perf, Group perf, SLA breaches, Volume, CSAT)
- Audit Log
- **System** (collapsible — Freshdesk, Managers, Sync Jobs, Settings, API Keys) — only renders when `user.hasRight('system.*')`.

**TopBar (`TopBar.vue`):** global search (Meilisearch), notifications bell (Reverb-powered), user menu (profile, logout).

Footer: `Connected to <freshdesk.domain>` + rate-limit remaining pill (clicks through to `/dashboard/system/freshdesk`).

---

## 3. Auth pages (`PublicLayout`)

### 3.1 `LoginPage.vue` — `/dashboard/login`

**Fields:** `email`, `password`, `remember me`.
**Buttons:** `Sign in`, text link `Forgot password?`.

**Workflow:**
1. `GET /sanctum/csrf-cookie` on mount.
2. Submit → `POST /api/v1/admin/auth/login`.
   - `200` → load `user`, redirect to `?redirect=` or `/dashboard`.
   - `401` → inline "Incorrect email or password."
   - `403` (not active / no dashboard role) → "Your account doesn't have dashboard access."
3. Loading state disables submit + shows spinner.

### 3.2 `ForgotPasswordPage.vue` / `ResetPasswordPage.vue`

Same shape as the portal counterparts. Hits `/api/v1/admin/auth/forgot` and `/api/v1/admin/auth/reset`.

---

## 4. `/dashboard` — `OverviewPage.vue`

Goal: at-a-glance health. No Freshdesk calls — reads only the local mirror.

**KPI cards (top row):** Open, Pending, Overdue (red), Unassigned, SLA breaches today, Avg first response time (7d).

**Widgets:**
- `Ticket volume` chart (30d, stacked by status) — `GET /api/v1/admin/reports/volume?window=30d&resolution=day`.
- `Top 5 agents by resolved tickets (7d)`.
- `Top 5 companies by open tickets`.
- `Recent activity feed` — last 20 entries from the local `audit_log` table (admin actions) + `sync_jobs` summaries. Live-updates via Reverb channel `admin.overview`.

**Buttons (header):**
- `Refresh` — `POST /api/v1/admin/overview/refresh` — enqueues a sync-all job; toast "Sync queued". Disables for 10s.
- `Export snapshot` — `POST /api/v1/admin/reports/volume/export` returns `{ download_url }` → SPA triggers download.

**Empty / unconfigured state:** if `freshdesk.test_ok === 0`, the page shows a single card: "Connect your Freshdesk account to start" → button `Configure now` → `/dashboard/system/freshdesk`.

---

## 5. Tickets

### 5.1 `TicketListPage.vue` — `/dashboard/tickets`

**Filter bar (`FilterBar.vue`):**
- Status (multi chip): Open, Pending, Resolved, Closed
- Priority (multi chip): Low, Medium, High, Urgent
- Assigned agent (autocomplete, from `GET /api/v1/admin/agents?autocomplete=…`)
- Group (dropdown)
- Company (autocomplete)
- Tag (multi)
- Date range (created_at / updated_at toggle)

**Saved views (`SavedViewsPicker.vue`):** All open · Unassigned · Overdue · My watch · Spam · Deleted. Persisted per user via `POST /api/v1/admin/saved-views`.

**Search:** free text, 300ms debounce, hits `GET /api/v1/admin/tickets?search=…` (Meilisearch over subject/description/requester).

**Table (`DataTable.vue`):**
- Checkbox (bulk)
- # (Freshdesk id, link to detail)
- Subject
- Requester (name + company badge)
- Agent avatar
- Group
- Status pill
- Priority icon
- `due_by` (red pill if overdue)
- Updated (relative)
- Tags (up to 3 + overflow)

**Row actions:** View · Assign · Change status · Delete.

**Bulk actions bar (appears when rows selected):**
- Assign agent (`AssignPicker`) → `POST /api/v1/admin/tickets/bulk-update { ids, properties: { responder_id } }`
- Change status / priority
- Add tag / remove tag
- Close with canned reply (modal: picks canned response from Freshdesk)
- Merge (modal: pick primary from selection) → `POST /api/v1/admin/tickets/merge`
- Delete (soft) → `POST /api/v1/admin/tickets/bulk-delete`
- Export selected CSV

**Header buttons:** `+ New ticket` → `/dashboard/tickets/new`, `Outbound email` (modal), `Refresh` (re-fetch + re-queue sync).

**Pagination:** cursor-based infinite scroll with "Load more" fallback. API returns `next` cursor.

**Realtime:** subscribes to `admin.tickets`; new/updated rows patch in-place; toast "5 tickets updated" when user is scrolled away.

### 5.2 `NewTicketPage.vue` — `/dashboard/tickets/new`

Full form (not a modal, since custom fields can be many):

- Requester — `AssignPicker` on contacts with inline-create fallback (`name` + `email` + `phone` → `POST /api/v1/admin/contacts` if requester doesn't exist)
- Subject (required)
- Description — `RichEditor.vue`
- Status (default Open)
- Priority (default Low)
- Source (default Portal)
- Type — dropdown from `config.ticketFields.type.choices`
- Assigned agent — optional
- Group — optional
- Company — optional autocomplete
- Product — dropdown
- Tags — `TagInput`
- CC emails — chip input
- Due by / First response due by
- Custom fields — rendered dynamically from `config.ticketFields`
- Attachments — `AttachmentDropzone`

**Buttons:** `Create`, `Create & open` (navigates to detail on success), `Cancel`.

**Submit:** `POST /api/v1/admin/tickets` (multipart). `201` → toast + redirect (or stay depending on button).

### 5.3 `TicketDetailPage.vue` — `/dashboard/tickets/:id`

Three-column layout (collapses to stacked on mobile).

**Left — Conversation:**
- Original description
- Replies + notes in time order. Notes highlighted yellow background, "Internal note" label. Each message: avatar, name, timestamp, body, attachment list.
- Per-message row actions (if `conversations.update|delete`): Edit, Delete, Download attachments.

**Composer tabs (`RichEditor` + attachments):**
- **Reply** — body, from_email dropdown (`email_config_id`), CC, BCC, attachments, canned response picker. Button `Send` (POST `/tickets/:id/reply`), secondary `Send & close` (sets status=Closed after sending).
- **Note** — body, `notify_emails` chip input, attachments, `private` toggle (default true). Button `Save note` (POST `/tickets/:id/note`).
- **Forward** — to_emails, cc, bcc, body, attachments. Button `Forward`.

**Right — Properties (inline-editable):**
- Status / Priority / Type / Source (dropdowns)
- Assigned agent (`AssignPicker`)
- Group
- Company, Product
- Tags (`TagInput`)
- `due_by`, `fr_due_by`
- Custom fields (from `config.ticketFields`)
- Requester block: avatar, name, email, phone, `View contact →`
- Associated tickets list

Inline edits call `PUT /api/v1/admin/tickets/:id` with just the changed field; optimistic UI.

**Top action bar:**
- `Close` (sets status=5)
- `Mark spam`
- `Merge…` (modal — pick primary from recent)
- `Forward` (switches composer to Forward tab)
- `Delete` (soft) / `Restore`
- `Open in Freshdesk ↗` (uses `freshdesk.domain`)

**Tabs under conversation:**
- `Conversation` (default)
- `Time entries` — table (agent, time_spent, billable, note, executed_at). `+ Add entry` modal: `time_spent (HH:MM)`, `note`, `billable`, `agent`, `executed_at`, `timer_running`. Timer button toggles `POST`/`PUT` to start/stop.
- `Satisfaction` — ratings + comments.
- `Activity log` — local audit entries for this ticket.

**Realtime:** subscribes to `admin.ticket.{id}`. Incoming `ConversationCreated` events prepend to thread.

---

## 6. Contacts

### 6.1 `ContactListPage.vue` — `/dashboard/contacts`

**Filters:** state (verified/unverified/blocked/deleted), company, tag, `updated_since`.
**Search:** name/email/phone.
**Columns:** avatar, name, email, phone, company link, tags, `view_all_tickets` toggle, last updated.
**Row actions:** View · Edit · Send invite · Make agent · Merge · Delete.
**Bulk:** add tag, send invite, delete, export.
**Header buttons:** `+ New contact`, `Import (CSV)`, `Export`.

Import flow:
- Click `Import` → opens modal → file picker + "Field mapping" preview.
- Submit → `POST /api/v1/admin/contacts/import` (multipart) → returns job id → page shows progress toast, links to `/dashboard/system/sync-jobs` on completion.

Export:
- `POST /api/v1/admin/contacts/export` with current filters → `{ download_url }` → SPA `window.location = download_url`.

### 6.2 `NewContactPage.vue`

Fields: name (required), email/phone/mobile/twitter_id/unique_external_id (at least one), company, job_title, address, language, time_zone, tags, custom_fields.
Buttons: `Create`, `Create & view`, `Cancel`.

### 6.3 `ContactDetailPage.vue` — `/dashboard/contacts/:id`

Header: avatar, name, email, phone, company link, tags, badges (verified/blocked).

**Top actions:** Edit, Send invite, Make agent, Merge, Soft delete, Hard delete (red), Restore, Open in Freshdesk.

**Tabs:**
- `Profile` — all fields editable; `other_emails` chip input; `other_companies` repeater.
- `Tickets` — reuse `TicketListPage` table, filtered to `requester_id=:id`.
- `Activity` — merges, invites, logins (local audit).

**Merge modal:** pick primary + secondaries via autocomplete, preview the merged field values, `Confirm merge`.

---

## 7. Companies

### 7.1 `CompanyListPage.vue` — `/dashboard/companies`

**Filters:** industry, account_tier, health_score, domain.
**Search:** name/domain.
**Columns:** name, domains, industry, tier, renewal_date, health_score, open tickets count.
**Row actions:** View · Edit · Delete.
**Header:** `+ New`, `Import`, `Export`.

### 7.2 `NewCompanyPage.vue`

Fields: name (required, unique), description, domains (chip input), note, health_score, account_tier, renewal_date, industry, custom_fields.

### 7.3 `CompanyDetailPage.vue`

**Tabs:** Profile, Contacts (filtered list), Tickets (filtered list), Stats (monthly volume chart, avg resolution, CSAT trend).

---

## 8. Agents

### 8.1 `AgentListPage.vue` — `/dashboard/agents`

**Columns:** avatar, name, email, type, available indicator, `ticket_scope`, groups, roles, last login.
**Row actions:** View · Edit · Deactivate · Delete.
**Header:** `+ New`, `Bulk create (CSV)`, `Sync from Freshdesk`.

### 8.2 `NewAgentPage.vue`

Fields: email (required), ticket_scope, occasional, signature (`RichEditor`), skill_ids (multi), group_ids (multi), role_ids (multi, from `GET /api/v1/admin/roles`).

### 8.3 `AgentDetailPage.vue`

Tabs: Profile, Assigned tickets (table), Time entries (table), Performance (30d: resolved count, avg first-response, avg resolution, CSAT).

---

## 9. Groups — `GroupListPage.vue`

Columns: name, description, agent count, `unassigned_for`, business_hours, auto-assign toggle.
Header: `+ New group` — modal.
Row actions: Edit (modal), Delete.

**Create/Edit modal:** name (required), description, `unassigned_for` (30m…3d), business_hour_id (from config), escalate_to (agent autocomplete), agent_ids (multi), auto_ticket_assign.

---

## 10. Reports

Each report is its own page, same scaffold:
- Header with title + date range picker + group filter + `Export CSV`
- One-or-two charts (Chart.js or ECharts)
- Data table underneath

### 10.1 `BacklogReportPage.vue`
Open/Pending counts bucketed by age (0–1d, 1–3d, 3–7d, 7d+). Grouped by group / agent toggle.

### 10.2 `AgentPerformancePage.vue`
Per agent: Assigned, Resolved, Avg first response, Avg resolution, CSAT avg. Sortable table + bar chart.

### 10.3 `GroupPerformancePage.vue`
Same breakdown at group level.

### 10.4 `SlaBreachReportPage.vue`
Trend chart + breach list with link to ticket detail.

### 10.5 `VolumeReportPage.vue`
Line chart: created vs resolved per day. Toggle stacked/area.

### 10.6 `CsatReportPage.vue`
Ratings distribution donut + comments table. Filter by agent/group.

---

## 11. `AuditLogPage.vue` — `/dashboard/audit-log`

Read-only table of local actions.
**Columns:** when, who, action, target, source (web/api/rest/system), summary.
**Filters:** user, action type, date range, target type.
**Row click:** side drawer with full JSON diff (`payload_before` vs `payload_after`).
**Buttons:** `Export CSV`.

---

## 12. System (superadmin only)

### 12.1 `FreshdeskConnectionPage.vue` — `/dashboard/system/freshdesk`

**Fields:**
- `domain` (text — `acme.freshdesk.com`)
- `api_key` (masked after save; "Edit key" reveals input)
- (display) Last rate-limit remaining: `<n>/<plan_limit>` + last updated time

**Buttons:**
- `Save connection` → `PUT /api/v1/admin/system/freshdesk`
- `Test connection` → `POST /api/v1/admin/system/freshdesk/test` → hits `GET /agents/me` via the integration; on success shows "Connected as <agent.name>. Plan limit: 5000/hr." toast + sets `freshdesk.test_ok=1`; on failure, inline error.
- Danger zone: `Clear local mirror` — confirm with typed `CLEAR` → clears all `freshdesk_id`'d rows and settings flag.

### 12.2 `ManagersPage.vue` — `/dashboard/system/managers`

Table of users with role `manager`.
**Columns:** name, email, assigned groups count, last login, active.
**Row actions:** Edit, Reset password, Deactivate, Delete.

**Create/Edit side-drawer:**
- `email` (required, unique), `name`, `password` (only on create — or "Send invite"), `is_active` toggle, `assigned_groups` (multi, from `GET /api/v1/admin/groups`).
- Save → `POST` or `PUT`. Scope save → `POST /api/v1/admin/system/managers/:id/scope { group_ids: [] }`.

### 12.3 `SyncJobsPage.vue` — `/dashboard/system/sync-jobs`

Two panes:
- **Schedule editor** — interval per resource (tickets, contacts, companies, agents, groups, conversations, time_entries). Min 1m; saving writes to `settings` keys (`sync.tickets_interval`, etc.).
- **Run history** — table of `sync_jobs` entries (resource, mode, status, started, duration, items upserted, items failed, error excerpt).

**Buttons:**
- Per resource: `Run now` → `POST /api/v1/admin/system/sync-jobs/:resource/run`
- `Full resync` (red, typed `RESYNC` confirm) → `POST /api/v1/admin/system/sync-jobs/full-resync`

**Realtime:** `admin.sync-jobs` channel; rows stream in as jobs start/finish.

### 12.4 `SettingsPage.vue` — `/dashboard/system/settings`

Sections grouped by `settings.group`:
- **General** — app name, logo upload, default timezone, portal URL
- **Portal** — `allow_public_registration`, `require_captcha`, `csat_on_resolve` toggles
- **Notifications** — Slack webhook (encrypted, masked), daily digest toggle
- **Email** — outbound from-name

**Button:** `Save` → `PUT /api/v1/admin/system/settings { updates: [...] }`.

### 12.5 `ApiKeysPage.vue` — `/dashboard/system/api-keys`

Table: name, prefix (`fk_xxxx…`), scopes (chips), created by, last used, status (active/revoked).
**Row actions:** Rotate, Revoke.

**Create side-drawer:** name, scopes (multi-select from the rights catalog), `expires_at` (optional).
On submit: server returns the **plaintext key once** in the response — show it in a modal with `Copy` button + big warning "This is the only time you'll see this key. Store it securely now." After closing, the UI can never retrieve it again (only the hash is stored).

---

## 13. `ProfilePage.vue` — `/dashboard/profile`

- Edit: display name, avatar, email (disabled — contact support), phone, timezone, password.
- Preferences: browser notifications, email digest.
- Sessions: `Sign out of other sessions` button.
- 2FA toggle (if enabled globally).

---

## 14. Shared components

| Component | Purpose |
|---|---|
| `DataTable.vue` | Sortable table, checkbox bulk, column persistence (local), empty state slot. Emits `sort`, `select`, `load-more`. |
| `FilterBar.vue` | Declarative filter schema → renders chip filters + autocomplete. |
| `BulkActionsBar.vue` | Sticky footer bar when rows selected. Slot-driven actions. |
| `AssignPicker.vue` | Searchable dropdown for agents/contacts; creates inline. |
| `RichEditor.vue` | Tiptap-based editor (bold, italic, lists, link, image paste). |
| `AttachmentDropzone.vue` | File drag-drop, validation, preview chips. |
| `KpiCard.vue` | Overview KPIs; delta + tooltip. |
| `ConfirmModal.vue` | Promise-based confirm. Supports typed confirmation. |
| `StatusPill.vue` / `PriorityIcon.vue` | Ticket status + priority visuals. |
| `TagInput.vue` | Tag chip editor with autocomplete from existing tags. |

---

## 15. Stores

Each list store follows the same pattern:
- State: `items`, `byId`, `filters`, `cursor`, `loading`, `error`, `selectedIds`.
- Actions: `fetch(filters)`, `fetchNextPage()`, `refresh()`, `create(payload)`, `update(id, patch)`, `destroy(id)`, per-domain specialized ones (e.g. `tickets.merge`, `contacts.sendInvite`).
- Getters: `byStatus`, `overdue`, etc. where useful.

`auth.ts`: `bootstrap()`, `login()`, `logout()`, `can(right)`, `hasRole(slug)`.
`config.ts`: parallel fetch of `/ticket-fields`, `/products`, `/business-hours`, `/sla-policies`, `/automations` on SPA boot. Cached 10 min.
`ui.ts`: toast queue, confirm prompt, global spinner.

---

## 16. API mapping recap (every call the admin SPA makes)

All paths are `/api/v1/admin/…`:

- Auth: `/auth/login`, `/auth/logout`, `/auth/me`, `/auth/forgot`, `/auth/reset`
- Overview: `/overview`, `/overview/refresh`
- Tickets: `/tickets` (index/create/show/update/destroy), `/tickets/:id/restore`, `/tickets/bulk-update`, `/tickets/bulk-delete`, `/tickets/merge`, `/tickets/:id/forward`, `/tickets/outbound-email`, `/tickets/:id/assign`, `/tickets/:id/conversations`, `/tickets/:id/reply`, `/tickets/:id/note`, `/tickets/:id/time-entries`, `/conversations/:id`, `/time-entries/:id`
- Contacts: `/contacts` (CRUD), `/contacts/:id/hard-delete`, `/contacts/:id/restore`, `/contacts/:id/send-invite`, `/contacts/:id/make-agent`, `/contacts/merge`, `/contacts/import`, `/contacts/export`
- Companies: `/companies` (CRUD), `/companies/import`, `/companies/export`
- Agents: `/agents` (CRUD), `/agents/bulk`
- Groups: `/groups` (CRUD)
- Reports: `/reports/{backlog|agent-performance|group-performance|sla-breaches|volume|csat}`, `/reports/:report/export`
- Audit: `/audit-log`
- Config (SPA dropdowns): `/ticket-fields`, `/products`, `/business-hours`, `/sla-policies`, `/automations`, `/roles`
- System: `/system/freshdesk` (GET/PUT), `/system/freshdesk/test`, `/system/managers` (CRUD + `/scope`), `/system/sync-jobs` (index + `/:resource/run` + `/full-resync`), `/system/settings` (GET/PUT), `/system/api-keys` (index/store/rotate/revoke)

---

## 17. UX conventions (apply to all admin pages)

- **Form pattern:** each page that writes maps one-to-one to a FormRequest + an Action; errors rendered inline from the 422 body.
- **Optimistic updates** for low-risk edits (status/priority/tag); revert on error with toast.
- **Destructive actions** always use `ConfirmModal`; hard-delete requires typed confirmation.
- **Empty states** use a shared component with an illustration + primary CTA.
- **Rate-limit awareness:** a global axios interceptor reads `X-RateLimit-Remaining` piggy-backed on our API responses (we forward it); the footer pill turns amber when <500 and red when <100. Writes that might hit rate limits show a gentle warning.
- **Realtime:** global Reverb client in `shared/realtime.ts`; each list page subscribes on mount, unsubscribes on unmount.
- **Responsive:** sidebar collapses to a drawer below `md`; tables become card lists below `md`.
- **Accessibility:** keyboard navigable, labeled inputs, focus rings, `aria-live` toast host.

---

## 18. Scope of this section

**Done in step 06:**
- All routes above in `router.ts`, all page files created
- Shared components listed above
- Pinia stores listed above
- Global axios instance with Sanctum XSRF + 401/419/422 interceptors
- Reverb client wired to private channels listed

**Not done here:**
- Tailwind/theme polish (depends on [02-themes-install.md](02-themes-install.md))
- Manager-only deltas (see [07-dashboard-manager-pages.md](07-dashboard-manager-pages.md))
- Action bodies on the backend (per-feature sections)
- End-to-end tests
