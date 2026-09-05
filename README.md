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
- [x] **Phase 3** — Dashboard and reports.
- [x] **Phase 4** — CSV/Excel export.
- [x] **Phase 5** — Admin panel (`/admin/companies`, `/admin/salesplay-accounts`).
- [x] **Phase 6** — AI weekly sales insight (`/reports/ai`).

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
saved. Tokens are managed per-account in the admin panel, not in `.env`.

## Admin panel

DynoPOS admins (`role=admin`, no `company_id`) get an **Admin** section in the
sidebar with two CRUD screens, gated by the `admin` route middleware
(`App\Http\Middleware\EnsureUserIsAdmin`) and `CompanyPolicy` /
`SalesplayAccountPolicy`:

- `/admin/companies` — create/edit/deactivate/delete customer companies.
  Deleting a company cascades to all of its users, SalesPlay accounts,
  products, and receipts.
- Each company's edit page lists its customer logins with a **Reset Password**
  action (`/admin/companies/{company}/users/{user}/password`). The self-service
  "forgot password" flow needs working outbound mail *and* a merchant who still
  has their signup mailbox; this is the fallback for when either is missing —
  the admin sets a password and passes it on by phone. The `{user}` binding is
  scoped to the `{company}` (so one company's URL can't reach another's login)
  and admins carry no `company_id`, so no admin account is reachable through
  it. Resetting also rotates `remember_token`, so a device that ticked
  "remember me" under the old password is signed out.
- `/admin/salesplay-accounts` — provision SalesPlay accounts for any company
  and manage their API tokens. The token field is write-only: it's never
  redisplayed after saving (the model hides it from serialization and
  encrypts it at rest), and leaving it blank on the edit form keeps the
  existing token unchanged.

## Running a manual sync

**First sync window.** The real API rejects an open-ended range — it answers
`INVALID_VALUE` / "The requested date range is not supported" on
`created_at_min` (with an HTTP 401 status, confusingly, which reads like an
auth failure but isn't). So an account's first sync — and the sync right after
an admin's Resync Penuh — names a concrete start date instead of asking for
all of history:

```
SALESPLAY_INITIAL_SYNC_MONTHS=12
```

Read via `services.salesplay.initial_sync_months`. Lower it if a first sync is
still rejected; raise it to keep more history. Later syncs are incremental and
resume from `last_synced_at` as before, so this only governs the very first
fetch.

**Why the history matters here:** SalesPlay puts past months behind its own
subscription, and the API enforces the same limit the UI does — a shop whose
plan has lapsed gets `INVALID_VALUE` on `created_at_min` rather than old
receipts. Anything DynoPOS has not already synced can therefore become
permanently unreachable, which is the whole reason this app exists. The
practical consequence: a merchant who subscribes for a single month can run
one **Resync Penuh** in that window and keep their full history in DynoPOS for
good.

That backfill has to be able to finish, so the pagination guard is
configurable too:

```
SALESPLAY_MAX_SYNC_PAGES=5000
```

It exists to stop a runaway pagination cursor, **not** to cap history — at 100
receipts a page the default covers 500,000 receipts, well past a busy outlet's
full backfill (one shop can ring up several thousand receipts a month, so 30
months is ~1,300 pages). A long sync logs its progress every 50 pages, so a
backfill that runs for minutes can be watched rather than guessed at.

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

## Dashboard & reports

- `/dashboard` — today/this-month/this-year sales, today's transaction count,
  a daily sales chart for the current month, and a monthly sales chart for
  the current year (Chart.js, via `resources/js/charts.js`). DynoPOS admins
  (no `company_id`) see a placeholder instead, since this page is always
  scoped to one company.
- `/reports/sales` — filterable (today / yesterday / this month / last month /
  custom range) transaction list; click a row for the full receipt detail
  (`/reports/sales/{receipt}`), including items and payments.
- `/reports/monthly` — monthly summary for a selected year (zero-filled
  months), with a year selector.
- `/reports/yearly` — summary grouped by calendar year, across all history.
- `/reports/products` — quantity sold + total sales per product, with the
  same date filter as the sales report.
- `/reports/ai` — the AI weekly review: this week's (or last week's) figures
  with an AI-written summary, highlights, and advice for next week (see
  [AI weekly insight](#ai-weekly-insight) below).
- All of the above (except `/dashboard`, gated separately) sit behind the
  `company` middleware (`EnsureUserHasCompany`), since reports only make
  sense for a customer user scoped to one tenant.
- Aggregation logic lives in `App\Support\Reports\SalesReportService`; date
  filter parsing in `App\Support\Reports\ReportPeriodResolver`.
- The authenticated UI uses a fixed dark sidebar (`resources/views/layouts/
  sidebar.blade.php`) with a red/orange brand accent, off-canvas on mobile.

## Export (CSV / Excel)

Every report page (`/reports/sales`, `/reports/monthly`, `/reports/yearly`,
`/reports/products`) has "Export CSV", "Export Excel", and "Export PDF"
buttons that export exactly what's currently on screen — the sales and
products exports carry the active date filter
(`?filter=...&from=...&to=...`) through to the download, and the monthly
export carries the selected `?year=`.

Built on [Laravel Excel](https://laravel-excel.com) (csv/xlsx) and
[barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf) (pdf):

- `App\Support\Reports\ExportFormat` is a backed enum (`csv`, `xlsx`, `pdf`)
  bound directly from the `{format}` route segment — Laravel resolves it
  and 404s automatically for anything else.
- Each report controller's `export()` action builds one
  `App\Support\Reports\ReportExport` — a small DTO bundling a Laravel Excel
  export object (`app/Exports/*.php`, for csv/xlsx) with a PDF Blade view +
  data (`resources/views/exports/pdf/*.blade.php`). Both sides are built
  from the exact same already-tenant-scoped data (from `SalesReportService`
  or a scoped `Receipt` query), so no export format can leak another
  company's data.
- `App\Http\Controllers\Concerns\ExportsReports::downloadReport()` is the
  single chokepoint every report controller's `export()` action calls,
  matching on `ExportFormat` to decide whether to stream a spreadsheet or
  render+download a PDF. **Adding a further format later** is just: a new
  `ExportFormat` case, a matching branch in that one method, and a button
  in the `x-export-buttons` component.
- PDF layout: `resources/views/exports/pdf/layout.blade.php` (DynoPOS
  branding, company name, report title/period, generated timestamp) is
  `@extend`ed by each report's PDF view, which only supplies the table.
- Tested with `Excel::fake()` and by rendering the PDF Blade views directly
  (see `tests/Feature/ExportTest.php`) — including that a customer's export
  never contains another company's rows.

## Email a report

Each report page also has an "Email Report" button that generates the same
PDF as the PDF export and emails it as an attachment — **only to the
authenticated user's own registered email**, never an arbitrary address
(the form doesn't even accept a `to` field), so this can't be turned into a
way to send someone else's sales data to a third party. Routes are
`POST`-only and rate-limited (`throttle:6,1`).

- `App\Mail\ReportMail` is a queued Mailable (`ShouldQueue`) built from the
  same `ReportExport` DTO the PDF export uses.
  `App\Http\Controllers\Concerns\EmailsReports::emailReport()` generates the
  attachment bytes (PDF via dompdf, or csv/xlsx via `Excel::raw()`) and
  queues the mail via `Mail::to($user->email)->queue(...)`.
- **Important:** attachment bytes are binary and not valid UTF-8, but queued
  jobs get JSON-encoded for storage (database/redis queue drivers) —
  holding raw binary in a public Mailable property throws
  `Illuminate\Queue\InvalidPayloadException` the moment it's actually
  pushed onto a real queue driver. `ReportMail` stores the attachment
  **base64-encoded** internally and only decodes it inside `attachments()`,
  when the queued job runs. `Mail::fake()` alone does *not* catch this class
  of bug (it never touches real queue serialization) — see
  `tests/Feature/EmailReportTest.php::test_report_mail_with_binary_attachment_survives_real_queue_serialization`,
  which pushes a real job onto the `database` queue driver to prove it
  survives serialization.
- Run a queue worker to actually deliver queued report emails (see
  [Queue setup](#queue-setup) above); with `MAIL_MAILER=log` (the local
  default) they're written to `storage/logs/laravel.log` instead of sent.

## AI weekly insight

`/reports/ai` answers the three questions a shop owner asks on a Sunday night:
how did this week go, what sold best, and what should I do next week. The page
always shows the week's own figures — total sales, transactions, average
receipt, net profit, sales per day, and the top 5 products — and, on top of
those, a written review generated by OpenAI.

- Week aggregation lives in `App\Support\Reports\WeeklySalesReportService`,
  which reuses `SalesReportService` and returns one
  `App\Support\Reports\WeeklySalesSnapshot` (Monday-to-Sunday, plus the same
  figures for the week before so the review can talk about the trend).
- The review itself comes from
  `App\Services\Ai\OpenAiSalesInsightGenerator`, bound to
  `App\Services\Ai\Contracts\SalesInsightGenerator` in `AiServiceProvider`.
  Swapping providers later means one new class and one binding — nothing in the
  controller or views knows which model wrote the text.
- The generator asks for a JSON object (`response_format: json_object`) and maps
  it to an `App\Services\Ai\DTO\SalesInsight` (headline, summary, highlights,
  advice). Anything malformed in the reply is dropped or raises
  `AiInsightException`, so a bad completion can never render as broken markup.
- Reviews are **generated on POST only**, never as a side effect of viewing the
  page (each generation costs an API call), rate-limited with `throttle:6,1`,
  and cached for 30 days per company + week + locale. The page shows the last
  generated review until the merchant presses "Jana Semula".
- **What leaves the app:** only the aggregates in
  `WeeklySalesSnapshot::toArray()` — totals, counts, product names, category
  names, payment method names. No receipts, customer names, emails, phone
  numbers, or staff names are ever sent, so no personal data reaches a third
  party (PDPA).

### Configuration

```
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
OPENAI_BASE_URL=https://api.openai.com/v1
OPENAI_TIMEOUT=60
```

Read via `config/services.php` (`services.openai.*`). The model is
configuration rather than a hardcoded string so it can be changed as OpenAI's
line-up moves, without touching any code.

**Until `OPENAI_API_KEY` is set**, the page still works: it shows the week's
figures as usual with a notice that the AI generator isn't set up yet, and the
generate button is hidden. Keep the key in the server's `.env` only — it is a
billed credential and `.env` is gitignored.

## Language (Bahasa Melayu / English)

The app defaults to Bahasa Melayu (`APP_LOCALE=ms` in `.env`) with English as
the fallback locale. A BM/EN toggle in the top bar (and on the login page)
switches the active locale, stored in the session — see `SetLocale`
middleware and `LocaleController`. Translation strings live in
`lang/ms/app.php` and `lang/en/app.php` (one `app.*` key per string, kept in
sync across both files); Carbon's locale follows the app locale automatically,
so dates and month names translate too. Covers the dashboard, reports,
sidebar/nav, and the Breeze login/password-reset/profile pages — including
validation error messages (`lang/ms/validation.php`, `auth.php`,
`passwords.php`, published via `php artisan lang:publish` and translated).

## Tax

Receipt totals are **tax-inclusive** — `total_money` from SalesPlay, i.e. what
the customer actually paid and what reached the till. SalesPlay's own dashboard
quotes "Gross sales" **before** tax and reports the tax separately under Tax
summary, so for a shop charging 6% the two systems show figures ~6% apart for
the same day. Both are correct; they measure different things.

Two consequences, both handled:

- `/reports/sales` shows a **Tax Collected** card whenever the period has tax,
  so the takings reconcile against SalesPlay's tax-exclusive figure. Shops with
  no tax never see the card.
- `/reports/pnl` deducts tax before profit. Tax is collected on the
  government's behalf and is not the shop's income — counting it as revenue
  makes a shop charging 6% look 6% more profitable than it is. The statement
  reads: total sales → tax collected → net sales → expenses → net profit, with
  the tax rows hidden for shops that charge none.

`SalesReportService::taxBetween()` is the single source for both. Covered by
`tests/Feature/TaxSeparationTest.php`.

## Timezone

`config/app.php` runs the app on `Asia/Kuala_Lumpur`, not UTC. Every shop here
trades in Malaysia and every report is about a Malaysian business day — "today's
sales", "this month", shift open/close times — and SalesPlay reports its receipt
timestamps in Malaysia local time too.

On UTC this drifted by the offset: between midnight and 8am local, `today()`
still resolved to the *previous* Malaysian day, so a late-night shop's sales
rung up after midnight were reported under the wrong day (and the current day
looked empty until 8am). Covered by
`tests/Feature/MalaysianBusinessDayTest.php`.

The API returns bare wall-clock strings with no offset
(`"2026-07-04 09:07:01"`), so `SalesPlayApiClient::parseApiDate()` parses them
explicitly in `API_TIMEZONE` rather than relying on the app's timezone happening
to match — otherwise every synced receipt silently shifts by whatever that
offset is.

**Note for existing installs:** receipt timestamps were already stored as
Malaysia wall-clock values, so they are now interpreted correctly with no
migration. Rows written by the app itself before the change (`created_at`,
`updated_at`, `last_synced_at`) were stored in UTC and will read 8 hours early;
`last_synced_at` self-corrects on the next sync (re-fetching a few extra hours
is idempotent — existing receipts are skipped).

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
