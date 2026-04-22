# Freshdesk Management App — Project Plan

Single-tenant companion SaaS built on top of the Freshdesk API v2. Adds a superadmin/manager dashboard and a lightweight customer portal layered on top of an existing Freshdesk account.

## Stack

- Laravel 13 (PHP 8.3)
- Horizon (queues), Reverb (WebSockets), Scout + Meilisearch (search)
- MySQL/Postgres for local mirror, Redis for cache + rate-limit guard

## Roles

- **superadmin** — configures the Freshdesk connection (domain + API key) via dashboard settings, manages managers, full visibility
- **manager** — dashboards, bulk ops, reports; may be scoped to groups
- **customer** — portal access only; sees their own (or their company's) tickets

## Catalog

| Doc | Purpose |
|---|---|
| [api-methods.md](api-methods.md) | Freshdesk endpoints we will consume (filtered down from the full v2 surface) |
| [api-payloads.md](api-payloads.md) | Request/response JSON shapes for each endpoint we use |
| [architecture.md](architecture.md) | Repositories layer, 3-surface routing (web / api-v1 / rest-v1), thin controllers → Actions |
| [dashboard-admin.md](dashboard-admin.md) | Superadmin pages under `/dashboard`: overview, tickets, contacts, companies, agents, groups, reports, audit, system |
| [dashboard-manager.md](dashboard-manager.md) | Manager pages under `/dashboard`: scoped to assigned groups, no system section |
| [portal-user.md](portal-user.md) | Customer-facing portal under `/portal`: login/register, submit request, my requests, detail, profile |
| [rights-catalog.md](rights-catalog.md) | Permission slug enumeration + role→rights map (source for seeders, policies, middleware) |

## Implementation sections

| Section | Purpose |
|---|---|
| [01-data-layer.md](01-data-layer.md) | All 17 tables: auth (users/roles/user_roles/role_rights), Freshdesk mirror, app-internal. Migrations, models, repos, seeders |
| [02-themes-install.md](02-themes-install.md) | Install frontend themes for customer portal and admin/manager dashboard (placeholder) |
| [03-routes-controllers-actions.md](03-routes-controllers-actions.md) | Full route tables (web / api-v1 / rest-v1), controller + action + FormRequest + Resource file trees, middleware aliases |
| [04-portal-pages.md](04-portal-pages.md) | Customer-facing Vue SPA pages under `/portal/*`: routing, auth pages, home, requests, profile; stores, components, API calls |
| [05-freshdesk-integration.md](05-freshdesk-integration.md) | Freshdesk integration: `app/Services/FreshdeskService` façade + `app/mixins/integrations/freshdesk/` (client, rate-limit, resources, DTOs, webhooks, fakes) |
| [06-dashboard-admin-pages.md](06-dashboard-admin-pages.md) | Admin dashboard Vue SPA under `/dashboard/*`: every page, filters, buttons, workflow, stores, API calls |
| [07-dashboard-manager-pages.md](07-dashboard-manager-pages.md) | Manager deltas on top of 06: hidden menu, scoped data, Assign Queue wizard, enforcement layers |
| [08-testing.md](08-testing.md) | Unit (PHPUnit) + Feature (PHPUnit) + Frontend (Vitest) + E2E (Playwright). Factories, FakeClient, CI, coverage targets |

## To be added later

- `data-model.md` — local mirror schema + migrations
- `features-admin.md` — superadmin/manager feature specs
- `features-portal.md` — customer portal feature specs
- `auth.md` — app auth (roles, magic link for customers)
- `settings.md` — admin-editable configuration fields
- `deployment.md` — Docker compose layout, env vars, secrets
