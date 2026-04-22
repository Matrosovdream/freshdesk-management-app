# Dashboard — Manager

Route prefix: `/dashboard` (shared with superadmin). Access gated by role `manager`.
Menu and data are **scoped to the manager's assigned groups** (set by superadmin in `/dashboard/system/managers`). A manager who has no assigned groups sees nothing but their Overview with empty states.

Left-nav menu (manager):
- Overview
- Tickets
- Contacts
- Companies
- Agents (read-only)
- Reports
- My profile

There is no `System`, no `Groups` CRUD, no `Audit Log`, no API keys page. Those are hidden at the menu level and blocked server-side by policy (`ManagerPolicy::canAccessSystem = false`).

---

## 1. `/dashboard` — Overview (manager)

**Purpose:** health of the groups this manager owns.

**Scope filter:** a pill at the top showing assigned groups (`Acme Support`, `Billing`). If multiple, a group dropdown filters all widgets.

**Cards:**
- Open tickets (in my groups)
- Unassigned (in my groups)
- Overdue (in my groups) — red
- SLA breaches today
- Tickets awaiting first response
- Avg first response time (7d)

**Widgets:**
- Queue snapshot (my groups): table of open tickets sorted by `due_by` ascending, first 10
- Workload by agent (horizontal bar: tickets assigned per agent in my groups)
- Aging backlog (0–1d, 1–3d, 3–7d, 7d+)
- Recent replies from my agents (last 10)

**Buttons:**
- `Refresh` (triggers sync for this scope)
- `Assign queue` → opens queue assignment wizard (auto-distribute unassigned tickets to online agents round-robin)

---

## 2. `/dashboard/tickets`

### 2.1 List
Same table as admin, but query is **forced to `group_id IN (manager.assigned_group_ids)`**. The `Group` filter is restricted to those groups only.

**Saved views (manager):** `My groups — open`, `Unassigned in my groups`, `Overdue in my groups`, `Awaiting first response`, `Escalated to me`, `Resolved today`.

**Row actions available:** View · Assign · Change status · Change priority · Add tag.
**Row actions NOT available:** Hard delete.

**Bulk actions:** Assign agent, Change status, Change priority, Add/remove tag, Close with canned reply, Merge, Soft delete.
**Blocked:** Bulk restore, Export-all-accounts CSV (only their scope export).

**Header buttons:** `+ New ticket` (auto-fills `group_id` = first assigned group), `Outbound email`, `Refresh`, `Saved views`.

### 2.2 Create ticket modal
Identical to admin, with the `Group` dropdown restricted to assigned groups (not optional — one must be chosen).

### 2.3 Ticket detail — `/dashboard/tickets/{id}`
- Blocks access if `ticket.group_id` is not in manager's scope → 403 page "This ticket belongs to a group you do not manage."
- All admin features except: `Hard delete` hidden, `Restore` hidden.
- Time entries: manager can view + add for themselves, cannot edit entries authored by others.

---

## 3. `/dashboard/contacts`

**Scope:** contacts that have at least one ticket in the manager's assigned groups (derived from the local mirror). Contacts with no ticket overlap are hidden from the list; direct URL access returns 403.

**Available:**
- List with filters and search
- View profile
- Edit basic fields (name, phone, job_title, tags, address, language, timezone)
- Send invite
- Merge (only between contacts in-scope)

**Blocked:**
- Hard delete
- Make agent
- Import / Export (full dataset operations are admin-only)

---

## 4. `/dashboard/companies`

**Scope:** companies that own at least one contact in-scope.

**Available:** list, view, edit basic fields (description, domains, note, health_score, account_tier, renewal_date, industry, custom_fields). View company's tickets tab is filtered to `group_id IN scope`.

**Blocked:** Delete, Import, Export-all.

---

## 5. `/dashboard/agents` (read-only directory)

**Purpose:** look up agents, see their load.

**Columns:** avatar, name, email, available, current open tickets (in manager's scope), groups.
**Row actions:** View · Message (opens internal note template on their next unassigned ticket — optional v2).
**Blocked:** create, edit, delete, deactivate, role changes.

Manager cannot see agents who are not in at least one of their assigned groups.

---

## 6. `/dashboard/reports`

Same report tabs as admin, all queries forced to manager scope.

- **Backlog** (my groups)
- **Agent performance** (only agents in my groups)
- **SLA breaches** (my groups)
- **Volume** (my groups)
- **CSAT** (my groups)

Buttons: date range picker, group filter (limited to assigned), `Export CSV` (scoped export only).

---

## 7. `/dashboard/profile`

- Edit: display name, avatar, password, notification preferences (email on new unassigned ticket in my groups; daily digest)
- Show: assigned groups (read-only), last login, 2FA toggle (if enabled globally)

---

## Access matrix recap

| Surface | Manager |
|---|---|
| `/dashboard` overview | ✓ scoped |
| `/dashboard/tickets` | ✓ CRUD within scope; no hard delete |
| `/dashboard/contacts` | ✓ read + edit within scope |
| `/dashboard/companies` | ✓ read + edit within scope |
| `/dashboard/agents` | ✓ read-only within scope |
| `/dashboard/groups` | ✗ hidden |
| `/dashboard/reports` | ✓ scoped |
| `/dashboard/audit-log` | ✗ hidden |
| `/dashboard/system/*` | ✗ hidden + 403 |

---

## Enforcement

- **Middleware:** `role:manager` on the route group, plus `ManagerScope` middleware that injects `assigned_group_ids` into the request and rejects URLs without scope.
- **Policies:** `TicketPolicy`, `ContactPolicy`, `CompanyPolicy`, `AgentPolicy` check `in_array($model->group_id, $user->assigned_group_ids)`.
- **Repo helpers:** every repo exposes a `scopedTo(array $groupIds)` fluent method so Actions can enforce scope once at the top of `handle()`.
- **UI:** menu items conditionally rendered by a shared `can()` helper reading the current user's role.
