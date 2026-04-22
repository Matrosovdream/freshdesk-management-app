# 04 — Portal Pages (Customer SPA)

Vue 3 SPA that lives at `/portal/*`, mounted from `resources/js/apps/portal/main.ts`. Builds on the UX rules already set in [portal-user.md](portal-user.md) — this file is the **implementation plan**: routes, pages, components, stores, and the API calls they make.

All data comes from `/api/v1/portal/*` (Sanctum SPA cookie auth). The Blade shell is the only thing the server renders; every view below is a Vue route.

---

## 0. SPA layout

```
resources/js/apps/portal/
├── main.ts                      # app bootstrap
├── router.ts                    # Vue router
├── App.vue                      # <RouterView/> + toast host
├── layouts/
│   ├── PublicLayout.vue         # minimal, centered — used for auth pages
│   └── AppLayout.vue            # top nav + container — used after login
├── pages/
│   ├── auth/
│   │   ├── LoginPage.vue
│   │   ├── RegisterPage.vue
│   │   ├── ForgotPasswordPage.vue
│   │   ├── ResetPasswordPage.vue
│   │   ├── VerifyEmailPage.vue
│   │   └── MagicLinkPage.vue
│   ├── HomePage.vue
│   ├── requests/
│   │   ├── RequestListPage.vue
│   │   ├── NewRequestPage.vue
│   │   └── RequestDetailPage.vue
│   └── ProfilePage.vue
├── components/                  # page-level building blocks
│   ├── RequestCard.vue
│   ├── StatusChip.vue
│   ├── Composer.vue             # reply composer (rich-text lite + attachments)
│   ├── AttachmentDropzone.vue
│   ├── CsatPrompt.vue
│   └── EmptyState.vue
└── stores/                      # Pinia stores
    ├── auth.ts
    ├── requests.ts
    ├── drafts.ts
    ├── config.ts                # ticket fields, products
    └── ui.ts                    # toasts, modals
```

Shared across apps (auth cookie, XSRF header, axios instance) lives under `resources/js/shared/`.

---

## 1. Router (`router.ts`)

```ts
const routes = [
  // Public
  { path: '/portal/login',              name: 'portal.login',    component: LoginPage,          meta: { layout: 'public', guest: true } },
  { path: '/portal/register',           name: 'portal.register', component: RegisterPage,       meta: { layout: 'public', guest: true, requires: 'publicRegistration' } },
  { path: '/portal/forgot',             name: 'portal.forgot',   component: ForgotPasswordPage, meta: { layout: 'public', guest: true } },
  { path: '/portal/reset',              name: 'portal.reset',    component: ResetPasswordPage,  meta: { layout: 'public', guest: true } },
  { path: '/portal/verify',             name: 'portal.verify',   component: VerifyEmailPage,    meta: { layout: 'public' } },
  { path: '/portal/magic',              name: 'portal.magic',    component: MagicLinkPage,      meta: { layout: 'public' } },

  // Authenticated
  { path: '/portal',                    name: 'portal.home',        component: HomePage,           meta: { auth: true } },
  { path: '/portal/requests',           name: 'portal.requests',    component: RequestListPage,    meta: { auth: true } },
  { path: '/portal/requests/new',       name: 'portal.requests.new',component: NewRequestPage,     meta: { auth: true } },
  { path: '/portal/requests/:id',       name: 'portal.requests.show', component: RequestDetailPage, meta: { auth: true }, props: true },
  { path: '/portal/profile',            name: 'portal.profile',     component: ProfilePage,        meta: { auth: true } },

  // Fallback
  { path: '/portal/:pathMatch(.*)*', redirect: { name: 'portal.home' } },
];
```

**Global beforeEach guard:**
1. On first navigation, if `auth.user` is unknown → call `GET /api/v1/portal/auth/me`. 401 → treat as guest.
2. `meta.auth && !user` → redirect to `/portal/login?redirect=<path>`.
3. `meta.guest && user` → redirect to `/portal`.
4. `meta.requires === 'publicRegistration'` and `config.allowPublicRegistration === false` → redirect to `/portal/login` with toast "Registration is disabled — ask your account manager for an invite."

---

## 2. Auth pages

### 2.1 `LoginPage.vue` — `/portal/login`

**Layout:** centered card, app logo, subtitle "Sign in to submit and track your requests."

**Tabs:**
- **Password** (default)
- **Email me a link**

**Fields (password tab):** `email`, `password`.
**Fields (magic link tab):** `email`.

**Buttons:**
- `Sign in` (submit, password tab)
- `Send me a link` (submit, magic-link tab)
- Text links: `Forgot password?`, `Create an account` (hidden when public registration is off)

**Workflow:**
1. Before first submit: `GET /sanctum/csrf-cookie`.
2. Password submit → `POST /api/v1/portal/auth/login { email, password }`.
   - `204/200` → store `user` → redirect to `?redirect=` or `/portal`.
   - `401` → inline error "Email or password is incorrect." (generic; no account enumeration).
   - `403 is_active=false` → inline "This account is disabled. Contact support."
3. Magic link submit → `POST /api/v1/portal/auth/magic-link { email }` → always show success toast "If an account exists, we've sent a link." (no enumeration).
4. Loading state: submit disabled + spinner inside the button.
5. Field-level validation errors render beneath each input from the `errors` map.

### 2.2 `RegisterPage.vue` — `/portal/register`

Rendered only when `config.allowPublicRegistration === true`. Otherwise guard redirects.

**Fields:**
- `name` (required)
- `email` (required, format)
- `company` (optional free text; matched server-side by domain)
- `phone` (optional)
- `password` (min 10, client-side strength meter)
- `password_confirmation`
- `captcha` (if `config.requireCaptcha === true`)
- `accept_terms` (checkbox, required)

**Buttons:** `Create account`, text link `Already have an account? Sign in`.

**Workflow:**
1. `POST /api/v1/portal/auth/register` → `201` with `{ verification_required: true }`.
2. Redirect to `/portal/verify?email=<email>` which shows "We sent a verification link to <email>. Check your inbox."
3. `422` → render field errors.

### 2.3 `ForgotPasswordPage.vue` — `/portal/forgot`

**Field:** `email`.
**Button:** `Email me a reset link`.
**Workflow:** `POST /api/v1/portal/auth/forgot { email }` → generic success message (no enumeration). Secondary link `Back to sign in`.

### 2.4 `ResetPasswordPage.vue` — `/portal/reset?token=…&email=…`

Reads `token` + `email` from query string.

**Fields:** `password`, `password_confirmation` (the email is read-only, shown for context).
**Button:** `Set new password`.
**Workflow:** `POST /api/v1/portal/auth/reset { token, email, password, password_confirmation }` → on success, auto-login cookie is set server-side → redirect to `/portal` with toast "Password updated."

### 2.5 `VerifyEmailPage.vue` — `/portal/verify?token=…`

Two variants:
- **Waiting variant** (no token, email shown in query) — informational card: "Check your inbox at `<email>`. We sent a verification link." Button `Resend email`.
- **Consume variant** (token present) — on mount, `POST /api/v1/portal/auth/verify { token }`.
  - Success → toast + redirect to `/portal`.
  - Expired/invalid → card with `Resend email` button that calls the resend endpoint.

### 2.6 `MagicLinkPage.vue` — `/portal/magic?token=…`

On mount: `POST /api/v1/portal/auth/magic-link/consume { token }`.
- Success → sets session cookie → redirect to `/portal`.
- Expired/invalid → card with a `Send me a new link` form (single email field).

---

## 3. Authenticated pages

### 3.1 `HomePage.vue` — `/portal`

**Greeting:** `Hi, {firstName} 👋`. Primary CTA button below: `+ Submit a new request` → `/portal/requests/new`.

**Sections:**
- **Your open requests** — up to 5 `RequestCard`s. Empty state: "You haven't submitted anything yet."
- **Awaiting your reply** — highlighted block; only rendered when there is at least one Pending request with last message from agent.
- **Resolved recently** — last 3 resolved requests; each shows an inline `CsatPrompt` if unrated.
- **Quick actions:** `Submit new request`, `View all requests`, `Edit profile`.

**Data fetch on mount:**
- `GET /api/v1/portal/requests?status=open&limit=5`
- `GET /api/v1/portal/requests?status=pending_reply&limit=5`
- `GET /api/v1/portal/requests?status=resolved&limit=3&unrated=1`

### 3.2 `RequestListPage.vue` — `/portal/requests`

**Header:** title `My requests`, primary button `+ New request`.
**Filter chips:** All · Open · Waiting on us · Waiting on you · Resolved · Closed. Active chip maps to an API param.
**Search input:** debounced 300ms.
**Company toggle:** visible only if `user.canViewCompanyTickets === true`; switch "Show all company requests". Off by default.

**Table/list:** mobile-first card list. Each card from `RequestCard.vue` shows subject, status chip, "2h ago", preview of last message, unread dot.

**Infinite scroll** with "Load more" fallback button.

**Empty state:** illustration + "You haven't submitted anything yet. Need help? `Submit a new request`."

**API:** `GET /api/v1/portal/requests?status=<>&search=<>&scope=own|company&cursor=<>`.

### 3.3 `NewRequestPage.vue` — `/portal/requests/new`

**Form fields (rendered by `config.ticketFields`):**
- `subject` (required)
- `description` (required, rich-text lite — bold/italic/link/lists only)
- `type` (optional dropdown — only if field is customer-editable)
- `priority` (hidden unless customer-editable; otherwise server default)
- `product_id` (optional, from `GET /api/v1/portal/products`)
- `attachments` (drag-drop multi-file; max 10 × 15MB; client + server enforced)
- Custom fields that have `displayed_to_customers: true` — rendered dynamically.

**Buttons:** `Submit`, `Save draft`, `Cancel`.

**Autosave:** every 30s or on blur → `POST /api/v1/portal/drafts { payload }`.

**Workflow:**
1. On mount, `GET /api/v1/portal/drafts` — if draft exists, prefill + show banner "Continuing your saved draft. `Discard`".
2. `Submit` → `POST /api/v1/portal/requests` (multipart if attachments). 
   - `201 { id }` → `clear draft` → redirect to `/portal/requests/{id}` with toast "Request #123 submitted".
   - `422` → field errors.
3. `Save draft` → explicit save + exit to `/portal/requests`.
4. `Cancel` → if dirty, confirm modal "Discard your changes?" → clear draft on confirm.

### 3.4 `RequestDetailPage.vue` — `/portal/requests/:id`

**Header block:**
- Subject
- `StatusChip` — human-labeled:
  - Open → "We're on it"
  - Pending → "Waiting for your reply"
  - Resolved → "Resolved"
  - Closed → "Closed"
- "Submitted on" + relative date
- Assigned agent (name + avatar, **no email or internal identifiers**)

**Thread (stacked):**
- Original description (customer)
- Ordered conversation: public replies from agents + the customer's replies (**internal notes are never fetched** — the API filters `private=false`).
- Each message: avatar + name + timestamp + body + attachment list. Customer messages right-aligned, agent messages left-aligned.

**Composer (`Composer.vue`):**
- Always rendered when status ≠ Closed.
- Rich-text lite editor.
- Attachment dropzone.
- Primary button `Send reply`. Secondary button `Reopen with this reply` (only when status=Resolved).
- Loading state disables the button + shows spinner.

**Action row (above composer):**
- `Mark resolved` — shown when status ∈ { Open, Pending }. Confirm modal "Mark this request as resolved?". On success toast + show CSAT prompt.
- `Reopen` — shown when status = Resolved. Confirm modal.

**CSAT:**
- On first render of a Resolved+unrated request, show `CsatPrompt.vue` above the thread. 1–5 emoji scale + optional comment. `POST /api/v1/portal/requests/{id}/rate`. Dismissible (stores a local flag so it doesn't re-show this session).

**Realtime:**
- On mount, subscribe to Reverb channel `portal.user.{user.id}`.
- On `ConversationCreated` event matching this ticket → prepend to thread, flash "New reply" indicator, scroll to bottom if user is already at bottom.

**Error boundaries:**
- `403` (ticket not owned by user / company scope mismatch) → full-page "You don't have access to this request." with `Back to my requests`.
- `404` → "This request doesn't exist or was deleted."

### 3.5 `ProfilePage.vue` — `/portal/profile`

**Three sections:**

**Profile**
- `name`, `phone`, `mobile`, `job_title`, `address`, `language`, `time_zone`, `avatar` (upload).
- Button `Save changes` → `PUT /api/v1/portal/profile`.

**Security**
- Current password, new password, confirm → `PUT /api/v1/portal/profile/password`.
- `Manage 2FA` (only if global 2FA is enabled; otherwise hidden).
- `Sign out of other sessions` button → `POST /api/v1/portal/auth/logout-others`.

**Account**
- `email` read-only — tooltip "Contact support to change your email."
- `company` read-only link.
- Danger zone: `Delete my account` → confirmation modal with typed confirmation (`type "DELETE"`) → `DELETE /api/v1/portal/profile`. On success, clear auth, toast, redirect to `/portal/login`.

---

## 4. Shared stores

### 4.1 `auth.ts` (Pinia)

State: `user`, `loading`, `error`.

Actions:
- `bootstrap()` — `GET /sanctum/csrf-cookie` then `GET /api/v1/portal/auth/me`. Swallows 401.
- `login({ email, password })` — `POST /api/v1/portal/auth/login`, sets `user`.
- `logout()` — `POST /api/v1/portal/auth/logout`, clears `user`, navigates to `/portal/login`.
- `magicLinkSend(email)`, `magicLinkConsume(token)`.
- `forgot(email)`, `reset(payload)`.
- `verify(token)`.

Getters: `isAuthenticated`, `firstName`, `canViewCompanyTickets` (derived from user's rights).

### 4.2 `requests.ts`

State: `list`, `byId`, `loadingList`, `cursor`, `filters`.

Actions: `load(filters)`, `loadNextPage()`, `fetch(id)`, `submit(payload)`, `reply(id, payload)`, `resolve(id)`, `reopen(id)`, `rate(id, { score, comment })`.

### 4.3 `drafts.ts`

Debounced `save(payload)` → `POST /api/v1/portal/drafts`. `load()`, `clear()`.

### 4.4 `config.ts`

State: `ticketFields`, `products`, `allowPublicRegistration`, `requireCaptcha`, `csatOnResolve`.

`load()` on SPA bootstrap — parallel fetch of:
- `GET /api/v1/portal/ticket-fields`
- `GET /api/v1/portal/products`
- Public config (a minimal unauthenticated `GET /api/v1/portal/config/public` — returns `allowPublicRegistration`, `requireCaptcha`, `csatOnResolve`). This endpoint is the only `/portal/*` API route that doesn't require auth.

### 4.5 `ui.ts`

Toast queue, global modal state, online/offline indicator.

---

## 5. Shared components

| Component | Purpose |
|---|---|
| `RequestCard.vue` | Card used in Home + RequestList. Subject, status chip, relative time, preview, unread dot. |
| `StatusChip.vue` | Colored pill translating ticket status to human label. |
| `Composer.vue` | Rich-text lite editor + attachment dropzone + submit button. Emits `submit(payload)`. |
| `AttachmentDropzone.vue` | File drag-drop, client-side validation (10 files, 15MB each). Emits added/removed files. |
| `CsatPrompt.vue` | Emoji scale + optional comment + submit. Dismissible. |
| `EmptyState.vue` | Illustration + title + description + primary CTA slot. |

---

## 6. UX rules (enforced across every page)

Already spelled out in [portal-user.md § "UX rules"](portal-user.md). Implementation notes:

- **Layout switcher:** each route picks `PublicLayout` or `AppLayout` via `meta.layout`. `App.vue` renders the chosen layout.
- **Plain language:** status copy lives in `StatusChip.vue` (single source).
- **Mobile-first:** Tailwind breakpoints `sm:`/`md:`/`lg:`; every page tested at 375px.
- **One primary CTA per page:** enforced by convention in components (one `PrimaryButton` allowed per `<template>`).
- **No numeric pagination:** infinite scroll + "Load more" on `RequestListPage`.
- **Accessibility:** all form inputs have labels + `aria-describedby`; focus trap inside modals; `aria-live="polite"` for toast host.
- **Error handling:** global axios interceptor turns API errors into toasts; form-level errors rendered inline from response body.

---

## 7. API mapping recap (every call the portal makes)

| Page | Verb + Path |
|---|---|
| Bootstrap | `GET /sanctum/csrf-cookie`, `GET /api/v1/portal/config/public`, `GET /api/v1/portal/auth/me` |
| Login | `POST /api/v1/portal/auth/login` |
| Logout | `POST /api/v1/portal/auth/logout` |
| Register | `POST /api/v1/portal/auth/register` |
| Magic link | `POST /api/v1/portal/auth/magic-link`, `POST /api/v1/portal/auth/magic-link/consume` |
| Verify email | `POST /api/v1/portal/auth/verify` |
| Forgot/reset | `POST /api/v1/portal/auth/forgot`, `POST /api/v1/portal/auth/reset` |
| Home | `GET /api/v1/portal/requests?...` |
| List | `GET /api/v1/portal/requests?status=&search=&scope=&cursor=` |
| Detail | `GET /api/v1/portal/requests/{id}` |
| Reply | `POST /api/v1/portal/requests/{id}/reply` |
| Resolve / Reopen | `POST /api/v1/portal/requests/{id}/resolve`, `.../reopen` |
| Rate | `POST /api/v1/portal/requests/{id}/rate` |
| Submit | `POST /api/v1/portal/requests` (multipart) |
| Drafts | `GET /api/v1/portal/drafts`, `POST .../drafts`, `DELETE .../drafts` |
| Config | `GET /api/v1/portal/ticket-fields`, `GET /api/v1/portal/products` |
| Profile | `GET/PUT /api/v1/portal/profile`, `PUT .../profile/password`, `DELETE .../profile` |

---

## 8. Scope of this section

**Done in step 04:**
- Vue router wired up for `/portal/*`
- All 12 portal pages created with the fields, buttons, and workflows above
- Pinia stores (`auth`, `requests`, `drafts`, `config`, `ui`)
- Shared components (`RequestCard`, `StatusChip`, `Composer`, `AttachmentDropzone`, `CsatPrompt`, `EmptyState`)
- Global axios instance with XSRF header + 401/422 interceptors
- Guard logic in `beforeEach`

**Not done here:**
- Visual polish / Tailwind theming (depends on [02-themes-install.md](02-themes-install.md))
- Dashboard (superadmin/manager) SPA — separate section
- Reverb subscription wiring beyond the detail page stub
- End-to-end tests
