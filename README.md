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
- [x] **Phase 2** — SalesPlay API client/sync service, `salesplay:sync`
      command, scheduler.
- [ ] **Phase 3** — Dashboard and reports.
- [ ] **Phase 4** — CSV/Excel export.
- [ ] **Phase 5** — Admin panel (`/admin/companies`, `/admin/salesplay-accounts`).

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

Laravel's scheduler drives the periodic SalesPlay sync — `salesplay:sync` is
registered in `routes/console.php` to run every 15 minutes, with
`withoutOverlapping()` and `onOneServer()` so a slow run never overlaps
itself and multiple app servers don't double-sync. Point a single cron entry
at the scheduler — do not add per-task cron entries:

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

These are read via `config/services.php` (`services.salesplay.*`) and consumed
by `App\Services\SalesPlay\SalesPlayApiClient`, so the endpoint can be changed
later without touching any sync/business logic — see
`App\Providers\SalesPlayServiceProvider`.

**Until `SALESPLAY_BASE_URL` is set, the app automatically uses
`App\Services\SalesPlay\SalesPlayMockApiClient`** instead of calling any real
API. It generates realistic fake receipts/items/payments so the sync
pipeline, dashboard, and reports can be built and demoed end-to-end before
the real SalesPlay endpoint is confirmed. Once `SALESPLAY_BASE_URL` is
configured, `SalesPlayServiceProvider` binds the real HTTP client instead —
no other code changes needed.

> The real client's request path/response shape
> (`App\Services\SalesPlay\SalesPlayApiClient`) is a provisional best-guess,
> clearly marked in the class docblock, since the real SalesPlay API docs
> aren't available yet. Only that one class needs to change once they are.

Each `salesplay_accounts` row has its own `api_token`, stored using Laravel's
`encrypted` Eloquent cast (backed by `APP_KEY`) and hidden from all model
serialization — the token is never returned in any API/JSON response once
saved. Tokens are managed per-account in the admin panel (Phase 5), not in
`.env`.

## Running a manual sync

```bash
php artisan salesplay:sync           # dispatches one queued job per active account
php artisan salesplay:sync --now     # runs all accounts synchronously, no queue worker needed
```

The command loops over every active `salesplay_accounts` row (belonging to an
active company), fetches transactions since `last_synced_at` (with
pagination via `SalesPlaySyncService`), skips receipts that already exist
(deduped by `salesplay_receipt_id`), creates their `receipt_items` and
`payments`, and updates `last_synced_at` / `last_sync_status` /
`last_sync_error`. A failure syncing one account is logged and does not stop
the others from syncing — proven by
`tests/Feature/SalesPlaySyncTest::test_command_isolates_failure_of_one_account_from_another`.
A failed account simply retries from its last successful `last_synced_at` on
the next run.

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
