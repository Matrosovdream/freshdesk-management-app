# Portal — Customer (End User)

Route prefix: `/portal` (NOT `/dashboard`). Public-facing surface used by end customers (ticket requesters). Access gated by role `customer`. Auth uses session + optional magic-link login. Customers are mapped 1:1 to a Freshdesk Contact by email.

**Design brief:** clean, simple, self-service. No jargon. No Freshdesk internals leaking (no group ids, no agent assignment details beyond agent name/avatar, no SLA timers, no internal notes).

Top-nav (customer):
- Home
- My requests
- New request
- Knowledge base (optional v2, links to `/solutions` if we enable later)
- Profile

---

## 1. `/portal/login`

**Layout:** single centered card.

**Two login methods (tabbed or stacked):**
- Email + password
- "Email me a login link" (magic link)

**Fields:** `email`, `password` (hidden for magic link).
**Buttons:** `Sign in`, `Send me a link`, `Forgot password?`, `Create an account`.
**Workflow:**
- Password login → verify → session → redirect to `/portal`.
- Magic link → create signed link valid 15min → email → click → session → `/portal`.
- Unknown email: generic "If an account exists, we've sent a link" (no enumeration).

---

## 2. `/portal/register`

**Visible only if** the superadmin has enabled `allow_public_registration` in settings. If disabled, page shows: "Your helpdesk is invite-only — ask your account manager for an invite."

**Fields:** name (required), email (required), company (optional, free text — matched to existing Freshdesk company by domain if possible), phone (optional), password (min 10, zxcvbn score ≥ 3), confirm password, CAPTCHA (if enabled).

**Buttons:** `Create account`, `Already have an account? Sign in`.

**Workflow:**
1. Validate form.
2. `CreatePortalAccountAction`:
   - Check if a Freshdesk contact exists by email (`GET /contacts?email=`).
   - If not, `POST /contacts` with name + email + phone + company.
   - Create local `User` row, role `customer`, link `freshdesk_contact_id`.
   - Send verification email.
3. On email verified → redirect to `/portal`.

---

## 3. `/portal` — Home

**Purpose:** landing page after login.

**Header:** "Hi, {first name} 👋" and a big primary CTA `+ Submit a new request`.

**Sections:**
- **Your open requests** — card list (up to 5), each card: subject, status badge, last updated, last message excerpt. Click → detail.
- **Awaiting your reply** — highlighted section if status is Pending and last message is from agent. Count + list.
- **Resolved recently** — last 3 resolved; each has a small "How did we do?" CSAT prompt if unrated.
- **Quick actions:** `Submit new request`, `View all requests`, `Edit profile`.

No stats, no charts. One screen they can read in 5 seconds.

---

## 4. `/portal/requests` — My requests

**Filters (simple):** `Status` chips — All · Open · Waiting on us · Waiting on you · Resolved · Closed.
("Waiting on us" = Open; "Waiting on you" = Pending. We translate Freshdesk statuses to human labels.)

**Search:** subject / body (Meilisearch, scoped to `requester_id = current user`).

**List layout (card list, mobile-friendly, not a dense table):**
Each card:
- Subject (bold)
- Status chip
- Last updated (relative, e.g. "2h ago")
- Preview of last message
- Unread dot if there's a new agent reply since last view

**Click →** `/portal/requests/{id}`.

**Company view toggle:** if the contact has `view_all_tickets: true` on their company, show a switch "Show all company requests" that broadens the list. Off by default.

**Empty state:** "You haven't submitted anything yet. Need help? `Submit a new request`."

---

## 5. `/portal/requests/new` — Submit a new request

**Purpose:** one simple form.

**Fields:**
- Subject (required)
- Description (required, rich text minimal — bold/italic/link/lists only, no HTML source)
- Type / Category (dropdown, optional — populated from `/ticket_fields` where `customers_can_edit = true`)
- Priority (visible only if the field is marked customer-editable; otherwise hidden and defaults to Low)
- Product (dropdown, optional, from `/products`)
- Attachments (drag-drop, max 10 files, 15MB each — enforced client + server)
- Custom fields (auto-rendered from `/ticket_fields` where `displayed_to_customers: true`)

**Buttons:** `Submit`, `Save draft`, `Cancel`.

**Workflow:**
1. FormRequest validates.
2. `SubmitTicketAction`:
   - `POST /tickets` with `requester_id = current user.freshdesk_contact_id`, `source = 2 (Portal)`.
   - Upsert into local mirror.
   - Fire Reverb event on `user.{id}` → toast "Request #123 submitted".
3. Redirect to `/portal/requests/{id}`.

**Draft saving:** autosave every 30s to local storage + on blur to server (`portal_drafts` table). "Save draft" button persists and exits.

---

## 6. `/portal/requests/{id}` — Request detail

**Header:**
- Subject
- Status chip (`Open` → "We're on it" · `Pending` → "Waiting for your reply" · `Resolved` → "Resolved" · `Closed` → "Closed")
- Submitted on (absolute date + relative)
- Assigned agent (name + avatar only, no email/internal identifiers)

**Body (stacked thread):**
- Original description
- Public replies from agents (with agent avatar + name)
- Customer's own replies
- **Internal notes are NEVER shown.** Filtered server-side by `private: false` and `incoming in [true, false]` where not a note.

**Attachments:** inline list per message, click to download.

**Reply composer (always visible if status ≠ Closed):**
- Rich text minimal
- Attachments
- Button `Send reply`
- Below the composer, if status = Resolved: secondary button `Reopen with this reply` (sets status back to Open).

**Actions:**
- `Mark resolved` — only if status is Open/Pending; confirms and updates status to Resolved via our Action (which also asks for CSAT on next load).
- `Reopen` — visible when status is Resolved; re-opens the ticket.

**CSAT prompt:** on first view of a Resolved ticket, a small card appears: "How did we do?" with 1–5 emoji scale and optional comment. Submits via `GET/POST` to satisfaction ratings endpoints. Dismissible. One rating per ticket.

**Unavailable to the customer:** priority changes (unless field is customer-editable), assignment, tags, merge, forward, time entries.

---

## 7. `/portal/profile`

**Fields (editable):**
- Display name
- Phone, mobile
- Job title
- Address
- Language
- Timezone
- Avatar (upload)

**Security section:**
- Change password
- Manage 2FA (if enabled globally)
- Active sessions (logout others)

**Account section:**
- Email (read-only — requires support to change)
- Company (read-only link to `company.name` if set)
- Delete my account → opens confirmation: soft-deletes local user + sets Freshdesk contact to "do not contact". Confirm text "DELETE".

**Workflow:** profile saves call `UpdatePortalProfileAction` → `PUT /contacts/{id}` → update local user + `freshdesk_contact_id` cache.

---

## 8. `/portal/forgot` and `/portal/reset/{token}`

Standard password reset flow. Token valid 60min. Enumeration-safe message.

---

## 9. `/portal/logout`

POST endpoint, clears session, redirects to `/portal/login`.

---

## Customer user model

- Role: `customer`
- Columns: `id`, `email`, `password_hash`, `name`, `freshdesk_contact_id` (fk mirror), `email_verified_at`, `last_login_at`, `is_active`, timestamps
- Linked 1:1 with local mirrored `contacts.freshdesk_id`
- Customers cannot reach `/dashboard/*` — middleware redirects to `/portal`
- Superadmins cannot reach `/portal/*` as a customer (they can preview read-only from `/dashboard/contacts/{id} → Impersonate` if we add that later)

---

## UX rules (apply to the whole portal)

- **Plain language:** "request" not "ticket", "submit" not "create", "resolved" not "closed-state=4".
- **Mobile-first:** every page works at 375px width without horizontal scroll.
- **Max one primary CTA per screen.** Secondary actions are text links or outlined buttons.
- **No pagination numbers** — infinite scroll on the requests list with a "Load more" fallback.
- **Status copy:**
  - Open → "We're on it"
  - Pending → "Waiting for your reply"
  - Resolved → "Resolved"
  - Closed → "Closed"
- **Notifications:** on new agent reply, email the customer + optional browser push (opt-in in profile).
- **Consistent empty states** with an illustration + one CTA.
- **Accessibility:** WCAG 2.1 AA, keyboard-navigable composer, labeled inputs, visible focus rings.
