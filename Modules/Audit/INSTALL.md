# Audit Module Installation

## 1. Backup first
Back up application files and every tenant database before deployment.

## 2. Copy the consolidated parcel
Copy `Modules/Audit` to `<project>/Modules/Audit` and `public/modules/audit` to `<project>/public/modules/audit`.

## 3. Enable/register the module
If your installation uses nwidart/laravel-modules:

    php artisan module:enable Audit

The module owns its provider, bootstrap route loader, views, translations, commands and assets. Do not add Audit routes to global/core route files.

## 4. Database
For a fresh tenant choose ONE method:

A. Laravel migrations:

    php artisan migrate

B. phpMyAdmin/manual SQL:
Import `SQL/Audit_Master.sql` into the tenant database.

When upgrading from v1.0.6 to v1.0.8 there is **no schema change** and no new SQL is required.

## 5. Permissions
Required module permissions are declared in `Config/permissions.php`:

- audit.view
- audit.run
- audit.findings.view
- audit.findings.resolve
- audit.rules.manage
- audit.schedules.manage
- audit.reports.view
- audit.reports.export

## 6. Sidebar
`Config/sidebar.php` remains the standalone sidebar manifest. The module name is **Audit**.

## 7. Assets
Public assets are included under `public/modules/audit`. Optional publish command:

    php artisan vendor:publish --tag=audit-assets --force

## 8. Clear cache

    php artisan optimize:clear

## 9. Diagnose

    php artisan audit:diagnose

A central CLI session should normally show `Tenancy initialized: NO`. That is safe and expected.

## 10. Run a tenant audit
CLI audit runs must explicitly select a tenant unless the command was started from an already initialized tenant context:

    php artisan audit:run --tenant=<tenant-id-or-domain>

Examples:

    php artisan audit:run --tenant=ishadi-pd
    php artisan audit:run --tenant=2003.nivasa.shop

Optional filters:

    php artisan audit:run --tenant=ishadi-pd --module=Finance
    php artisan audit:run --tenant=ishadi-pd --rule=SYS-SCHEMA-001

The command prints the resolved tenant and tenant database before running.

## 11. Browser verification
Open `/audit` on the tenant domain while logged in. Tenant middleware initializes the tenant before Audit executes.

## 12. Scheduled audits
Run one tenant:

    php artisan audit:scheduled --tenant=<tenant-id-or-domain>

Or all configured tenants:

    php artisan audit:scheduled --all-tenants

The module scheduler is enabled by default from v1.0.15, uses `--all-tenants`, and can be disabled with `AUDIT_SCHEDULED_ENABLED=false`. The server must also run Laravel `schedule:run` every minute.

## Safety notes
- Audit reads operational ERP data and writes only to `audit_*` tables.
- `audit:run` refuses to silently run in central CLI context.
- Route/controller integrity checking does not autoload every controller class.
- Each rule records progress into the current `audit_runs.context` JSON.
- Stale runs older than the configured threshold are recovered as failed before a new run starts.

### v1.0.10 Finance audit note
`FIN-BAL-001` does not assume that every `account_transactions.transaction_id` is a complete journal. It validates only explicit `pair_at_id` debit/credit counterparts. No SQL or migration change is required from v1.0.8.

### Updating v1.0.10 to v1.0.11
Overwrite the Audit module and public Audit assets from the parcel, then run `php artisan optimize:clear`. No SQL import or migration is required for this update.

### Upgrade v1.0.11 -> v1.0.12
No SQL or migration is required. Overwrite the Audit module and `public/modules/audit` assets, run `php artisan optimize:clear`, then hard-refresh `/audit`. The CSS/JS URLs include `v=1.0.12` to prevent stale browser/proxy assets.

### v1.0.13 upgrade
No SQL or migration is required when upgrading from v1.0.12. Replace the Audit module files and run `php artisan optimize:clear`. Browser audits with a selected business now use SQL aliases safely. Previous `Audit rule could not complete` warnings are automatically resolved once the corrected rule completes successfully.

### v1.0.14 upgrade
No SQL or migration is required when upgrading from v1.0.13. Replace the Audit module files and `public/modules/audit` assets, run `php artisan optimize:clear`, then hard-refresh `/audit/rules`. The Rules page now stays within the browser viewport and uses the same professional visual standard as the other Audit pages.

### v1.0.17 PDF export note
No Composer package is required for Audit PDF export. The module will use an existing DomPDF installation when available and otherwise uses its own standalone PDF renderer. No SQL change is required from v1.0.16.


### v1.0.18 Central Multi-Tenant Audit upgrade
No SQL or migration is required when upgrading from v1.0.17. Replace the Audit module files and `public/modules/audit` assets, then run `php artisan optimize:clear`.

On configured central domains, open `/audit` to use the Central Audit interface. It can audit/report the central database and configured tenant databases with searchable multi-select Tenant/Data Source, Business and Location filters. Business and Location options are loaded after one or more data sources are selected. Leaving the source selector blank means All.

Central Audit opens tenant databases one at a time. A missing/inaccessible tenant or a tenant without Audit tables is reported/skipped safely without stopping other selected sources. No operational ERP data is copied or edited; only `audit_*` records are written in the database being audited. Existing tenant-domain `/audit` pages remain unchanged.

### v1.0.19 Central connection restoration fix

Central `/audit` now safely restores the central database connection after every tenant probe, including when a tenant database cannot be opened. No SQL or migration change is required.

## v1.0.20 central tenant resolution fix
Central Audit resolves tenant IDs against the central tenant collection before falling back to model primary-key/domain lookups. Central source discovery also resets to the central connection before enumerating sources. No SQL change is required.
