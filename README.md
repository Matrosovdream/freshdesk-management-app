# Freshdesk Management App

## Overview

A Laravel + Vue dashboard for managing Freshdesk data — tickets, contacts, companies, agents, groups, and reports — with a separate customer portal. The stack runs on PHP-FPM, PostgreSQL, Redis, Horizon, and Reverb, orchestrated via Docker Compose.

## Demo Access Credentials, I know it's safe, for real production site it won't be used!

The seeders provision three demo accounts:

- Super Admin: `admin@example.test` (PIN: 9999)
- Manager: `manager@example.test` (PIN: 8888)
- Customer: `customer@example.test` (PIN: 7777)

All accounts accept the password `password` if PIN login is unavailable.

## Freshdesk Developer Account

To work against a real Freshdesk instance you'll need a developer account and an API key.

**Option 1 — Freshworks Developer Account (recommended for integration work):**

1. Sign up at [developers.freshworks.com](https://developers.freshworks.com/docs/getting-started/freshdesk/sign-up/).
2. From the developer portal, open **Products and Accounts** and spin up a free Freshdesk trial instance.
3. Note the assigned `*.freshdesk.com` subdomain — this is your API base URL.

**Option 2 — Plain 14-day trial:**

1. Sign up at [freshdesk.com/signup](https://freshdesk.com/signup) (no credit card required).
2. Verify via the activation email.

**Getting the API key:**

Inside your Freshdesk instance, click your profile picture (top-right) → **Profile Settings** → **View API Key**. Add the subdomain and API key to your `.env` file to enable the integration.

## Local Development Setup

### Initial Configuration

1. Clone and navigate to the repository
2. Copy `.env.example` to `.env`
3. Launch the development environment: `docker compose -f compose.dev.yaml up -d --build`
4. Install dependencies via `composer install` and `npm install`
5. Generate application key with `php artisan key:generate`
6. Execute `php artisan migrate --seed` to initialize the database
7. Start frontend development with `npm run dev`

The application becomes accessible at `http://localhost:8080`.

## Production Cloud Deployment

A DigitalOcean Droplet ($24/month with 2 vCPU, 4GB RAM) suits this application well. The deployment process involves:

- Installing Docker and creating a Traefik network
- Configuring Traefik as a reverse proxy with automatic SSL via Let's Encrypt
- Setting DNS records through Cloudflare
- Cloning the repository and configuring environment variables
- Building frontend assets in a containerized Node environment
- Launching services via `compose.prod.yaml`
- Running migrations and seeding operations

Key environment variables include database credentials, Redis configuration, and Reverb WebSocket parameters.
