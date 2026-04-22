# 02 — Themes Install

Two Vue 3 SPAs, one Vite config with two entries, two Blade shells.

## Templates chosen

| SPA | Template | Source |
|---|---|---|
| `/dashboard/*` | **Sakai** (PrimeVue official admin) | https://github.com/primefaces/sakai-vue |
| `/portal/*` | **vue-landing-page** (aesthetic reference) + custom PrimeVue pages | https://github.com/FahimAnzamDip/vue-landing-page |

The landing-page template lives at `resources/js/web-reference/` as a style reference only. The portal SPA itself is built with PrimeVue + Tailwind 4 for consistency with the dashboard; its unauthenticated pages borrow the landing template's centered-card aesthetic.

Both templates were downloaded into `project-plan/templates/{dashboard,web}/` for reference (kept under source control).

## Installed dependencies (package.json)

```
dependencies:
  @primeuix/themes ^2.0.0
  axios ^1.7.0
  chart.js ^4.4.0
  pinia ^2.2.0
  primeicons ^7.0.0
  primevue ^4.5.4
  tailwindcss-primeui ^0.6.0
  vue ^3.4.34
  vue-router ^4.4.0

devDependencies:
  @primevue/auto-import-resolver
  @tailwindcss/vite
  @vitejs/plugin-vue
  laravel-vite-plugin
  sass
  tailwindcss ^4
  unplugin-vue-components
  vite
```

Laravel side:
- `laravel/sanctum ^4.0` added to `composer.json` (cookie-based SPA auth).

Run:
```bash
docker compose -f compose.dev.yaml exec workspace composer install
docker compose -f compose.dev.yaml exec workspace php artisan vendor:publish --provider="Laravel\\Sanctum\\SanctumServiceProvider"
docker compose -f compose.dev.yaml exec workspace php artisan migrate
docker compose -f compose.dev.yaml exec workspace npm install
docker compose -f compose.dev.yaml exec workspace npm run build
```

## Frontend layout

```
resources/
├── css/
│   └── portal.css                  # tailwind + primeicons for portal
├── js/
│   ├── shared/
│   │   └── http.js                 # axios + XSRF cookie handling
│   ├── apps/
│   │   ├── dashboard/              # Sakai — verbatim clone
│   │   │   ├── App.vue
│   │   │   ├── main.js             # imports @/assets/tailwind.css + styles.scss
│   │   │   ├── router/
│   │   │   ├── layout/
│   │   │   ├── components/
│   │   │   ├── service/
│   │   │   ├── views/
│   │   │   └── assets/             # Sakai's tailwind.css + styles.scss (self-contained)
│   │   └── portal/                 # custom, PrimeVue-based
│   │       ├── App.vue
│   │       ├── main.js
│   │       ├── router/
│   │       ├── layouts/            # PublicLayout, AppLayout
│   │       ├── pages/              # 11 pages per 04-portal-pages.md
│   │       ├── components/         # RequestCard, StatusChip, Composer, …
│   │       └── stores/             # auth, requests, drafts, config, ui
│   └── web-reference/              # vue-landing-page source — read-only reference
└── views/
    └── apps/
        ├── dashboard.blade.php     # @vite(resources/js/apps/dashboard/main.js)
        └── portal.blade.php        # @vite(resources/css/portal.css + .../portal/main.js)
```

## Vite configuration

[vite.config.js](../vite.config.js) registers two JS entries + one CSS entry via `laravel-vite-plugin`:
- `resources/js/apps/dashboard/main.js`
- `resources/js/apps/portal/main.js`
- `resources/css/portal.css`

Aliases:
- `@dashboard` → `resources/js/apps/dashboard`
- `@portal` → `resources/js/apps/portal`
- `@shared` → `resources/js/shared`
- `@` → `resources/js/apps/dashboard` (keeps Sakai's internal `@/...` imports working)

PrimeVue components are auto-imported via `unplugin-vue-components` + `@primevue/auto-import-resolver`.

## Sakai customisation

Sakai was cloned as-is. Later steps (06, 07) replace its demo views (`views/uikit/*`, `views/pages/*`) with real admin pages and add a Sanctum-aware axios client under `@shared/http.js`. The layout, menu, topbar, and theme configuration stay unchanged in this step.

## Portal customisation

The portal doesn't reuse the landing-page template's code — it reimplements the same clean/minimal aesthetic using PrimeVue components so theme tokens and dark mode stay unified with the dashboard. The landing template stays under `resources/js/web-reference/` purely as a visual reference for spacing, typography, and section structure.

## Blade shells

Both shells are minimal: CSRF meta tag + one `@vite([...])` directive + `<div id="app"></div>`. Nothing server-rendered beyond that.

## Sanctum + CORS

- [config/sanctum.php](../config/sanctum.php) — stateful domains from `SANCTUM_STATEFUL_DOMAINS`.
- [config/cors.php](../config/cors.php) — `supports_credentials: true`, paths: `api/*`, `sanctum/csrf-cookie`, `downloads/*`, `rest/*`.
- [config/auth.php](../config/auth.php) — `sanctum` guard added.
- [.env](../.env) additions: `SANCTUM_STATEFUL_DOMAINS`, `SESSION_DOMAIN`, `CORS_ALLOWED_ORIGINS`.

Middleware stack is set in `bootstrap/app.php` via `statefulApi()`.

## Build verification

```bash
docker compose -f compose.dev.yaml exec workspace npm run build
```

Expected: two JS bundles (dashboard, portal) + one CSS bundle emitted under `public/build/assets/`.
