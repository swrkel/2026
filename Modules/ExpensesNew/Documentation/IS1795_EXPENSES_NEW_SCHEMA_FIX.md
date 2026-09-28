# IS1795 – Expenses New database schema readiness

## Confirmed cause

The module routes were loading, but the tenant database did not contain the original
EXPNEW_001–EXPNEW_004 core tables. The supplied module archive starts at later parcels
(EXPNEW_005–EXPNEW_011), so `expnew_expenses` and the other core operating tables were
never created. The dashboard therefore failed while calculating today's expenses.

## Correction

- `EnsureExpensesNewSchema` runs after `tenant.context`, so it always works on the
  active tenant database rather than the central database.
- `SchemaReadinessService` creates the missing core tables, repairs missing columns,
  seeds only global command-centre widget definitions, and records a per-database
  schema version after a successful check.
- The check is idempotent and scoped only to Expenses New (`expnew_*`).
- A simultaneous first request is handled safely by rechecking the table/column after
  a create/alter race.
- When the database user cannot create tables, the module returns a clear module-only
  503 page/JSON response and logs the exact database error instead of showing the
  full Symfony exception page.
- `balance_amount` is kept compatible with the canonical `due_amount` value.
- Several command-centre entity table names were corrected to match the SQL schema.
- Approval and command-centre totals now use the real `total_amount` and `due_amount`
  columns.

## Tenant SQL

Run this file manually on each tenant database when automatic table creation is not
permitted:

`Modules/ExpensesNew/Database/sql/00_EXPENSES_NEW_CORE_SCHEMA_IDEMPOTENT.sql`

For a partially installed tenant database, run the column repair immediately after it:

`Modules/ExpensesNew/Database/sql/01_EXPENSES_NEW_CORE_COLUMN_REPAIR_IDEMPOTENT.sql`

The SQL is safe to re-run: it uses `CREATE TABLE IF NOT EXISTS` and guarded inserts.

For a clean tenant database that also needs all later EXPNEW_005–EXPNEW_011 tables, run:

`Modules/ExpensesNew/Database/sql/00_EXPENSES_NEW_FULL_MASTER_ALL_STAGES_IDEMPOTENT.sql`

## Deployment

1. Upload the changed files into the Laravel project root.
2. Run `php artisan optimize:clear`.
3. Open `/expenses-new` once. The first request prepares the active tenant schema.
4. Confirm the Laravel log contains no `Expenses New schema readiness failed` entry.
