# Dashboard — Superadmin

Route prefix: `/dashboard` (shared with managers). Access gated by role `superadmin`.
Session auth via `web` middleware. The superadmin sees **everything** and is the only role that can configure the Freshdesk connection, manage managers, and reach destructive/system screens.

Left-nav menu (superadmin):
- Overview
- Tickets
- Contacts
- Companies
- Agents
- Groups
- Reports
- Audit Log
- **System** (superadmin-only section)
  - Freshdesk Connection
  - Managers & Roles
  - Sync Jobs
  - Settings
  - API Keys

---

## 1. `/dashboard` — Overview

**Purpose:** at-a-glance health of the helpdesk.

**Cards (top row):**
- Open tickets (count, delta vs yesterday)
- Pending tickets
- Overdue (past `due_by`) — red
- Unassigned
- SLA breaches today
- Avg first response time (last 7d)

**Widgets:**
- Ticket volume chart (last 30d, stacked by status)
- Top 5 agents by resolved tickets (last 7d)
- Top 5 companies by open tickets
- Recent activity feed (last 20 sync events: created/updated/replied)

**Buttons:**
- `Refresh` — triggers `SyncTicketsAction` dispatch, toast on queued
- `Export snapshot` — PDF/CSV of current overview

**Workflow:** data pulled from local mirror, auto-refresh every 60s via Reverb broadcast.

---

## 2. `/dashboard/tickets`

### 2.1 List page

**Filters (top bar):**
- Status (multi): Open, Pending, Resolved, Closed
- Priority (multi): Low, Medium, High, Urgent
- Assigned agent (autocomplete)
- Group (dropdown)
- Company (autocomplete)
- Tag (multi)
- Date range (created_at / updated_at toggle)
- Saved views: `All open`, `Unassigned`, `Overdue`, `My watch`, `Spam`, `Deleted`

**Search:** free-text (Meilisearch over subject + description + requester name/email).

**Table columns:**
- `#` (Freshdesk id, link)
- Subject
- Requester (name + company badge)
- Assigned agent (avatar)
- Group
- Status (colored pill)
- Priority (icon)
- `due_by` (red if overdue)
- Updated
- Tags

**Row actions:** View · Assign · Change status · Delete (soft)

**Bulk actions (checkbox select):**
- Assign agent
- Change status
- Change priority
- Add tag / remove tag
- Close with canned reply
- Merge (opens modal to pick primary)
- Delete (soft) / Restore
- Export selected (CSV)

**Buttons (header):**
- `+ New ticket` — opens create modal
- `Outbound email` — opens outbound email modal
- `Refresh`
- `Saved views` dropdown

**Workflow:** every mutation goes through its Action, which calls Freshdesk then upserts the local mirror. Row updates in-place via Reverb.

### 2.2 Create ticket modal

**Fields:**
- Requester — autocomplete on contacts; fallback fields `name`, `email`, `phone`
- Subject (required)
- Description (rich text / HTML, required)
- Status (default Open)
- Priority (default Low)
- Source (default Portal)
- Type (dropdown from Freshdesk ticket fields)
- Assigned agent (autocomplete, optional)
- Group (dropdown, optional)
- Company (autocomplete, optional)
- Product (dropdown, optional)
- Tags (multi)
- CC emails (chip input)
- Due by / First response due by (datetime)
- Custom fields (rendered from `/ticket_fields`)
- Attachments (multi-file)

**Buttons:** `Create`, `Create & open`, `Cancel`

### 2.3 Ticket detail — `/dashboard/tickets/{id}`

**Layout:** 3 columns (left: conversation, center: timeline, right: properties).

**Left (conversation thread):**
- Original description
- Replies + notes in order (notes highlighted yellow, private)
- Per-message: edit, delete, download attachments

**Reply composer (tabbed):**
- Tab `Reply` — `body`, `from_email` dropdown, `cc_emails`, `bcc_emails`, attachments, canned-response picker
- Tab `Note` — `body`, `notify_emails`, attachments, `private` (default true)
- Tab `Forward` — `to_emails`, `cc_emails`, `bcc_emails`, `body`, attachments
- Buttons: `Send`, `Send & close`, `Save draft`, `Cancel`

**Right panel (properties — editable inline):**
- Status · Priority · Type · Source
- Assigned agent · Group
- Company · Product
- Tags
- `due_by`, `fr_due_by`
- Custom fields
- Requester block (name, email, phone, `View contact →`)
- Associated tickets list

**Top actions:** `Close`, `Mark spam`, `Merge…`, `Forward`, `Delete`, `Restore`, `Open in Freshdesk ↗`

**Tabs:** `Conversation` · `Time entries` · `Satisfaction` · `Activity log`

**Time entries tab:**
- Table: agent, time_spent, billable, note, executed_at
- `+ Add entry` modal: `time_spent (HH:MM)`, `note`, `billable`, `agent`, `executed_at`, `timer_running`
- Timer button (start/stop) — writes to local first, syncs on stop

---

## 3. `/dashboard/contacts`

### 3.1 List

**Filters:** state (verified/unverified/blocked/deleted), company, tag, `updated_since`.
**Search:** name / email / phone.
**Columns:** avatar, name, email, phone, company, tags, `view_all_tickets`, last updated.
**Row actions:** View · Edit · Send invite · Make agent · Merge · Delete.
**Bulk actions:** add tag, send invite, delete, export.
**Buttons:** `+ New contact`, `Import (CSV)`, `Export`.

### 3.2 Contact detail — `/dashboard/contacts/{id}`

**Header:** avatar, name, email, phone, company link, tags, badges (verified/blocked).
**Actions:** `Edit`, `Send invite`, `Make agent`, `Merge…`, `Soft delete`, `Hard delete`, `Restore`, `Open in Freshdesk ↗`.

**Tabs:**
- `Profile` — all fields editable; other_emails, other_companies, address, job_title, language, time_zone, custom_fields
- `Tickets` — list filtered by `requester_id` (same columns as Tickets list)
- `Activity` — merges, invites, logins (from local audit)

**Merge modal:** pick primary + secondaries, preview merged fields, confirm.

### 3.3 New contact modal
Fields: name (required), email/phone/mobile/twitter_id/unique_external_id (one required), company, job_title, tags, custom_fields.

---

## 4. `/dashboard/companies`

### 4.1 List
**Filters:** industry, account_tier, health_score, domain.
**Search:** name / domain.
**Columns:** name, domains, industry, tier, renewal_date, health_score, open tickets count.
**Row actions:** View · Edit · Delete.
**Buttons:** `+ New company`, `Import`, `Export`.

### 4.2 Detail — `/dashboard/companies/{id}`
**Tabs:**
- `Profile` — name, description, domains (chip input), note, health_score, account_tier, renewal_date, industry, custom_fields
- `Contacts` — list of contacts where `company_id = X`
- `Tickets` — all tickets for this company
- `Stats` — monthly ticket volume, avg resolution, CSAT

---

## 5. `/dashboard/agents`

### 5.1 List
**Columns:** avatar, name, email, type (support/field/collaborator), available (toggle read-only), ticket_scope, groups, roles, last login.
**Row actions:** View · Edit · Deactivate · Delete (downgrades to contact).
**Buttons:** `+ New agent`, `Bulk create (CSV)`, `Import from Freshdesk`.

### 5.2 Detail / Edit — `/dashboard/agents/{id}`
Fields: email, ticket_scope (Global/Group/Restricted), occasional, signature (rich text), skill_ids, group_ids (multi), role_ids (multi).
Tabs: `Profile`, `Assigned tickets`, `Time entries`, `Performance (last 30d)`.

---

## 6. `/dashboard/groups`

### 6.1 List
Columns: name, description, agents count, `unassigned_for`, business_hours, auto-assign toggle.
Row actions: Edit · Delete.

### 6.2 Create/Edit modal
Fields: name (required), description, `unassigned_for` (30m…3d), business_hour_id, escalate_to (agent), agent_ids (multi), auto_ticket_assign.

---

## 7. `/dashboard/reports`

Tabs, each rendered from local mirror (fast, no Freshdesk hits):
- **Backlog** — open/pending by age bucket (0–1d, 1–3d, 3–7d, 7d+)
- **Agent performance** — per agent: assigned, resolved, avg first response, avg resolution, CSAT avg
- **Group performance** — same breakdown per group
- **SLA breaches** — list + trend chart
- **Volume** — created vs resolved per day
- **CSAT** — ratings distribution, comments

Each report: date range picker, group-by selector, `Export CSV` button.

---

## 8. `/dashboard/audit-log`

Read-only table of all actions performed in this app (not Freshdesk).
Columns: when, who (user), action, target, diff, source (web/api/rest/webhook).
Filters: user, action type, date range, target type.
Row click → full JSON diff drawer.

---

## 9. `/dashboard/system` *(superadmin only)*

### 9.1 `/dashboard/system/freshdesk`
- Fields: `domain` (e.g. `acme.freshdesk.com`), `api_key` (encrypted, masked after save)
- Buttons: `Test connection` → calls `GET /agents/me`, shows agent identity + plan
- Last-seen rate-limit headers display
- Danger: `Clear local mirror` (confirm text input)

### 9.2 `/dashboard/system/managers`
- List of app users with role `manager`
- Columns: name, email, group scope, last login, active
- Create/edit: email (required), name, password reset link, assigned groups (scope filter for dashboards), active toggle
- Delete (soft)

### 9.3 `/dashboard/system/sync-jobs`
- List of Horizon job runs relevant to Freshdesk sync
- Columns: job, started, finished, items processed, status
- Buttons: `Run tickets sync now`, `Run contacts sync now`, `Run companies sync now`, `Run agents sync now`, `Full resync` (destructive, confirm)
- Schedule editor: interval per resource (min 1m, default 2m)

### 9.4 `/dashboard/system/settings`
General settings editable in UI:
- App name, logo
- Default timezone
- Portal URL (public customer portal origin)
- Email from-name (for outbound)
- Feature toggles: allow public registration, require CAPTCHA, CSAT on resolve
- Notification prefs: Slack webhook URL, daily digest on/off

### 9.5 `/dashboard/system/api-keys`
Keys used by external systems to call our `/api/v1` endpoints.
Columns: name, last 4, scopes, created by, last used, active.
Create modal: name, scopes (multi: `tickets.read`, `tickets.write`, `contacts.*`, etc.), expires.
Row actions: Rotate, Revoke.

---

## Access matrix (superadmin vs manager)

| Section | Superadmin | Manager |
|---|---|---|
| Overview | ✓ full | ✓ scoped to assigned groups |
| Tickets | ✓ all | ✓ only in assigned groups |
| Contacts | ✓ all | ✓ read + edit (no hard delete) |
| Companies | ✓ all | ✓ read + edit |
| Agents | ✓ CRUD | ✗ read-only directory |
| Groups | ✓ CRUD | ✗ read-only |
| Reports | ✓ all | ✓ scoped to assigned groups |
| Audit Log | ✓ | ✗ |
| System/* | ✓ | ✗ (hidden from menu) |

---

## Cross-cutting conventions

- **Forms:** every form is a Laravel FormRequest → DTO → Action. Server errors rendered inline.
- **Tables:** Inertia + a shared `DataTable` component; column config, saved views persisted per user.
- **Toasts:** every Action dispatch returns `{ ok, message }`; shown via Reverb channel `user.{id}`.
- **Open in Freshdesk:** every entity detail page has a `↗` link to the native Freshdesk URL.
- **Destructive actions:** confirmation modal with typed confirmation (`type "DELETE"`).
- **Empty states:** each table has an empty illustration + primary CTA to create the first record or trigger sync.
