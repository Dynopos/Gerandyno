# DynoPOS Cloud Report

Customer portal for DynoPOS merchants to view their historical sales reports.
Sales data is synced from the SalesPlay API into DynoPOS's own MySQL database,
so customers keep access to historical reports even if SalesPlay limits how
far back its own reporting goes.

**Architecture:**

```
SalesPlay API → Laravel Sync Service → MySQL Database → DynoPOS Cloud Report Dashboard
```

**Tech stack:** Laravel (latest stable) · PHP 8.3+ · MySQL · Blade + Tailwind CSS ·
Laravel Breeze · Laravel Scheduler · Laravel Queue.

The system is multi-tenant: every customer user belongs to a `company`, and
data (SalesPlay accounts, receipts, products, etc.) is scoped to that company
via a global Eloquent scope + authorization policies — a customer can never
see another company's data.

## Project status

This repository is being built in phases. Current status:

- [x] **Phase 1** — Laravel setup, authentication (Breeze), multi-tenant
      database schema, tenant isolation.
- [ ] **Phase 2** — SalesPlay API client/sync service, `salesplay:sync`
      command, scheduler.
- [ ] **Phase 3** — Dashboard and reports.
- [ ] **Phase 4** — CSV/Excel export.
- [ ] **Phase 5** — Admin panel (`/admin/companies`, `/admin/salesplay-accounts`).

The database schema, config, and encryption plumbing for the SalesPlay
integration are already in place (see below) even though the sync command
itself lands in Phase 2.

## Installation

Requirements: PHP 8.3+, Composer, Node.js + npm, MySQL 8+.

```bash
git clone <repo-url> dynopos-cloud-report
cd dynopos-cloud-report

composer install
npm install

cp .env.example .env
php artisan key:generate
```

## Database setup

1. Create a MySQL database and update `.env`:

   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=dynopos_cloud_report
   DB_USERNAME=root
   DB_PASSWORD=
   ```

2. Run migrations:

   ```bash
   php artisan migrate
   ```

3. (Optional) Seed demo data — creates a DynoPOS admin, one demo company with
   a customer user, a SalesPlay account, and sample receipts:

   ```bash
   php artisan db:seed
   ```

   Demo logins (password `password` for both):
   - `admin@dynopos.test` — DynoPOS admin (`role=admin`, no company, full
     cross-tenant access via `/admin`).
   - `customer@dynopos.test` — customer user scoped to "Kedai Demo Sdn Bhd".

   Public self-registration is disabled — customer accounts are always
   provisioned by an admin (with a `company_id`) so every user has a tenant.

## Building frontend assets

```bash
npm run dev    # local development (Vite)
npm run build  # production build
```

## Running the app locally

```bash
php artisan serve
```

## Queue setup

Background jobs (SalesPlay sync jobs, exports, etc.) run through Laravel's
queue. For local development the `database` queue driver (the default) works
out of the box once you've migrated the `jobs` table. Run a worker with:

```bash
php artisan queue:work
```

In production, run `queue:work` under a process supervisor (e.g. Supervisor)
so it restarts automatically, and deploy `php artisan queue:restart` after
each deploy to pick up new code.

## Scheduler setup

Laravel's scheduler drives the periodic SalesPlay sync (planned for every 15
minutes once the `salesplay:sync` command lands in Phase 2). Point a single
cron entry at the scheduler — do not add per-task cron entries:

```cron
* * * * * cd /path/to/dynopos-cloud-report && php artisan schedule:run >> /dev/null 2>&1
```

For local development, run the scheduler in the foreground instead:

```bash
php artisan schedule:work
```

## SalesPlay API configuration

The real SalesPlay API endpoint has not been confirmed yet, so the base URL
and version are deliberately left blank rather than hardcoded. Configure them
in `.env` once known:

```
SALESPLAY_BASE_URL=
SALESPLAY_API_VERSION=
SALESPLAY_TIMEOUT=30
```

These are read via `config/services.php` (`services.salesplay.*`) and will be
consumed by `SalesPlayApiService` (Phase 2), so the endpoint can be changed
later without touching application code.

Each `salesplay_accounts` row has its own `api_token`, stored using Laravel's
`encrypted` Eloquent cast (backed by `APP_KEY`) and hidden from all model
serialization — the token is never returned in any API/JSON response once
saved. Tokens are managed per-account in the admin panel (Phase 5), not in
`.env`.

## Running a manual sync

*(Lands in Phase 2.)* Once implemented, a manual sync of all active SalesPlay
accounts will be run with:

```bash
php artisan salesplay:sync
```

The command will loop over every active `salesplay_accounts` row, fetch
transactions since `last_synced_at` (with pagination), skip receipts that
already exist (deduped by `salesplay_receipt_id`), and update
`last_synced_at`. A failure syncing one account is logged and does not stop
the others from syncing.

## Running tests

```bash
php artisan test
```

Tests run against an in-memory SQLite database (see `phpunit.xml`), so no
MySQL setup is required to run the test suite.

## Multi-tenancy & security notes

- Every tenant-scoped model (`SalesplayAccount`, `Product`, `Receipt`) uses
  the `BelongsToCompany` trait, which applies a global `CompanyScope`
  restricting all queries to the authenticated user's `company_id`. Admin
  users (`role=admin`, no `company_id`) are exempt so `/admin` can manage
  data across all tenants.
- Authorization is additionally enforced via policies (`ReceiptPolicy`,
  `SalesplayAccountPolicy`) for defense in depth.
- SalesPlay API tokens are encrypted at rest and never exposed in serialized
  output.
- All state-changing routes go through Laravel's standard CSRF protection
  and form request validation.
