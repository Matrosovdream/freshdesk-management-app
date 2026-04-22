# 07 — Dashboard Pages (Manager)

Managers share the **same SPA** as superadmins (same Vue app, same router, same pages). This file describes the deltas: menu items that disappear, data that gets scoped, and actions that are disabled. All UI described in [06-dashboard-admin-pages.md](06-dashboard-admin-pages.md) applies unless overridden here.

Scope is enforced both in the SPA (to hide UI) and on the backend (source of truth — the `manager.scope` middleware injects `assigned_group_ids` into every `/api/v1/admin/*` request). The SPA trusts the backend; UI rules below are for cleanliness, not security.

---

## 1. Identification

A user is treated as "manager" when:
- `auth.user.roles` contains `manager` AND
- `auth.user.roles` does NOT contain `superadmin`

A user with both roles renders the superadmin menu. Rights enforcement still runs per-right regardless of role badge.

---

## 2. Menu (Sidebar)

Hidden items when the user is only `manager`:

| Item | Rule |
|---|---|
| Groups | hidden (`groups.view` not granted to managers) |
| Audit Log | hidden (`audit.view` not granted) |
| **System** (entire section) | hidden (no `system.*` rights) |

Visible items:
- Overview
- Tickets
- Contacts
- Companies
- Agents *(read-only, see below)*
- Reports (all 6 report pages remain, but data is scoped)
- Profile

The sidebar also shows a small chip under the user avatar listing their assigned groups (e.g. "Billing · Tier-2"). If the manager has **no** assigned groups, every page renders an empty state with CTA "Ask an admin to assign you to a group."

---

## 3. Overview — `/dashboard`

Scope rule: every widget is filtered server-side to `group_id IN assigned_group_ids`.

Differences vs admin:

- Under the page title: a pill list of assigned groups. If there are multiple, a dropdown filters all widgets to a single group.
- KPI card `SLA breaches today` — shown only for groups in scope.
- Widget `Top 5 agents by resolved tickets` — filtered to agents whose groups intersect scope.
- Widget `Top 5 companies by open tickets` — filtered to companies with at least one ticket in scope.
- Recent activity feed is limited to activity touching in-scope tickets.

Header buttons:
- `Refresh` — visible but calls a scoped refresh endpoint behind the scenes.
- `Export snapshot` — visible only if `reports.export` is granted (managers get it by default).
- Added: `Assign queue` button — opens the queue-assignment wizard (see § 4.2 below). Visible only when `tickets.assign` is granted.

Empty-state for a manager with zero assigned groups:
> _"You're not assigned to any groups yet. An admin needs to assign you before data shows up here."_ — no KPIs rendered.

---

## 4. Tickets — `/dashboard/tickets`

### 4.1 List

Backend forces `group_id IN scope`. The SPA also:

- **Group filter** in `FilterBar` is restricted to the manager's assigned groups (other groups don't appear in the dropdown).
- **Saved views** swap out to: `My groups — open`, `Unassigned in my groups`, `Overdue in my groups`, `Awaiting first response`, `Escalated to me`, `Resolved today`. The `Spam` and `Deleted` views are hidden.
- **Bulk actions:**
  - Available: Assign agent (picker limited to agents in scope), Change status/priority, Add/remove tag, Close with canned reply, Merge (both tickets must be in scope — validated server-side), Soft delete.
  - Hidden: Bulk **restore**, Bulk **hard delete**, Export-all (only scoped export allowed).
- Header button `+ New ticket` — opens `NewTicketPage` with `group_id` pre-filled to the first assigned group; the `Group` field is required (not optional) and is constrained to the manager's scope.
- Header button `Outbound email` — hidden (right `tickets.outbound_email` not granted).

### 4.2 Assign Queue wizard (manager-specific)

Triggered from Overview and from Tickets list when there are unassigned tickets.

Steps:
1. Source: `Unassigned tickets in my groups`.
2. Target: multi-select available agents (auto-suggests agents who are in the same groups and `available=true`).
3. Strategy: `Round-robin`, `Least loaded`, or `Manual drag-drop`.
4. Preview table showing which agent will get which ticket.
5. `Assign all` button → single `POST /api/v1/admin/tickets/bulk-update` per target agent.

### 4.3 Detail — `/dashboard/tickets/:id`

- If `ticket.group_id` is not in scope → render **403 page** "This ticket belongs to a group you don't manage." with `Back to tickets` link. (Backend already 403s; SPA renders the friendly page.)
- Top action bar differences:
  - Hidden: `Delete (hard)`, `Restore`, `Mark spam` (rights-dependent — hidden if not granted).
  - Visible: `Close`, `Merge…`, `Forward`, `Delete (soft)`.
- Properties panel:
  - `Group` dropdown is restricted to assigned groups (can't move a ticket out of scope).
- Time entries tab:
  - Can create new entries (for self).
  - Can edit/delete only entries the manager authored; others are read-only (backend enforces).
- Conversations:
  - `Delete conversation` action hidden (right not granted).

---

## 5. Contacts — `/dashboard/contacts`

Scope rule: contacts that have at least one ticket in an assigned group. Backend derives the filter; the SPA simply calls `/api/v1/admin/contacts` and trusts the response.

Differences:

- **Hidden header buttons:** `Import`, `Export-all`. Per-row `Export` (single contact) still available.
- **Hidden row/row-detail actions:** Hard delete, Make agent.
- Merge modal: restricts selection to contacts in scope; picker disallows cross-scope merges.
- The detail page's `Tickets` tab is filtered to the manager's scope.

---

## 6. Companies — `/dashboard/companies`

Scope rule: companies that own at least one in-scope contact.

Differences:

- Hidden: `+ New company`, `Delete`, `Import`, `Export-all`.
- Editing limited to: description, domains, note, health_score, account_tier, renewal_date, industry, custom_fields. `name` becomes read-only.
- The `Tickets` tab on detail filters to scope.

---

## 7. Agents — `/dashboard/agents` (read-only)

Scope rule: agents that share at least one assigned group with the manager.

Differences:

- Hidden: `+ New`, `Bulk create`, `Edit`, `Deactivate`, `Delete`.
- Columns unchanged, except `Current open tickets (scoped)` replaces the full count — shows only tickets in the manager's scope assigned to this agent.
- Detail page tabs: `Profile` (read-only), `Assigned tickets` (filtered), `Time entries` (filtered), `Performance (30d, scoped)`.

---

## 8. Groups — hidden

The route `/dashboard/groups` is not registered in the manager-visible menu. Direct URL access is blocked by a guard:

```ts
if (meta.right && !auth.can(meta.right)) → redirect('/dashboard')
```

So a manager who types `/dashboard/groups` gets redirected to Overview with a toast "You don't have access to this page."

---

## 9. Reports — `/dashboard/reports/*`

All 6 report pages remain, with scope forced server-side. SPA differences:

- **Group filter** is restricted to assigned groups only.
- Default group filter value = the manager's assigned groups (not "all").
- `Export CSV` remains; the export endpoint applies the same scope.
- `Agent performance` report lists only agents in-scope.

No reports are removed; the layout is identical. A small banner at the top of each report reads: "Showing data for your groups: _Billing, Tier-2_."

---

## 10. Audit Log — hidden

Route is not in the manager menu. Direct URL access → same redirect/toast as Groups.

---

## 11. System — hidden

All `/dashboard/system/*` routes are blocked by the route guard (`meta.role === 'superadmin'`). Managers never see this section in the sidebar.

---

## 12. Profile — `/dashboard/profile`

Fields editable by the manager:

- Display name, avatar, phone, timezone
- Password change
- Notification preferences: *On new unassigned ticket in my groups* (toggle), *Daily digest* (toggle)
- Active sessions — `Sign out of other sessions`

Read-only (set by admin):
- Email
- Assigned groups (display only, with admin contact link)
- Role

---

## 13. SPA enforcement (recap)

Scope logic runs in three places:

1. **Route guard** in `router.ts` — checks `meta.right` / `meta.role` against `auth.user`. Hides URL access.
2. **Conditional rendering** in components — buttons/menu items not visible when the right isn't granted. Uses the `v-can="'right.slug'"` directive backed by `auth.store.can()`.
3. **Store actions** skip API calls when the user clearly lacks the right (prevents unnecessary 403 round trips). Still tolerates backend 403s as the source of truth.

The **backend is authoritative**: even if the SPA were fully open, the API would still 403 every forbidden call because `manager.scope` middleware + `right:*` middleware run on each request.

---

## 14. Deltas summary (quick reference)

| Feature | Admin | Manager |
|---|---|---|
| Overview scope | global | scoped to assigned groups |
| Tickets — bulk restore / hard delete | ✓ | ✗ |
| Tickets — outbound email | ✓ | ✗ |
| Tickets — Assign Queue wizard | — | ✓ |
| Tickets — Group filter | all groups | assigned only |
| Contacts — Import / Export-all | ✓ | ✗ |
| Contacts — Hard delete / Make agent | ✓ | ✗ |
| Companies — Create / Delete / Import | ✓ | ✗ |
| Agents — CRUD | ✓ | read-only |
| Groups page | ✓ | hidden |
| Reports | global | scoped |
| Audit Log | ✓ | hidden |
| System | ✓ | hidden |
| Profile | ✓ | ✓ (no 2FA admin) |

---

## 15. Scope of this section

**Done in step 07:**
- Sidebar and router guards applied based on roles/rights
- Scope pill on Overview, Tickets, Reports
- Assign Queue wizard component on Tickets
- Scoped filter wiring on list pages
- 403-friendly page for out-of-scope ticket detail URL

**Not done here (covered elsewhere or later):**
- Backend middleware `manager.scope` — in [03-routes-controllers-actions.md](03-routes-controllers-actions.md)
- Backend right enforcement — in [01-data-layer.md](01-data-layer.md) + middleware stubs
- Manager creation/scope assignment UI — admin-side, in [06](06-dashboard-admin-pages.md) § 12.2
