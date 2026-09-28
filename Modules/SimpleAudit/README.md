# Simple Audit

Standalone Laravel module for a single-code, multi-tenant/multiple-database ERP with multiple Businesses, Locations and Stores per tenant.

## Scope in this parcel

The first sidebar page is **Purchase Audit**. It provides the five required audit sections:

- Purchase — Qty, Unit Cost, Total, Discount and Tax.
- Stock Movements — Before, Purchases, Purchase Return, Stock Adjustment, After and Difference.
- Supplier Payments — Before, After and Difference.
- Accounts — Before, After and Difference, limited to Purchase, Purchase Return, Stock Adjustment and Supplier Payment effects.
- Supplier Ledgers — Before, After and Difference, limited to suppliers affected by those audit sources.

The page includes Tenant / Business / Location / Store context, type-and-filter dropdowns with keyboard up/down selection, the standard date ranges, instant filtering, universal search, pagination, row-count selector, column visibility, Excel, CSV, Print, PDF, Email and WhatsApp secure-link sharing.

Clickable audit values open a detail popup. Hovering a clickable value also loads a small cached preview of the related transaction detail.

## Standalone design

All module PHP, routes, views, CSS, JS, language strings, permissions, services, reports, utilities and database objects live under `Modules/SimpleAudit`.

The module does **not** include or modify another module's source files. It reads the ERP's existing operational data tables as audit sources. Every table owned by Simple Audit uses the `sau_` prefix.

The module can run on the current tenant DB or, from the central application, resolve a selected tenant using the existing `tenants` / `domains` master data. Persistent Simple Audit data stays inside the selected tenant DB.

## Audit capture

The tenant installation creates five `sau_` tables and 21 read-side audit capture triggers. The triggers only insert into `sau_` audit tables; they do not update the ERP source transaction rows.

A stock baseline is captured from `variation_location_details` when Simple Audit is installed. Later stock balance changes are snapshotted automatically.

## Important historical coverage

The supplied ERP database stores physical stock balances at **Location** level in `variation_location_details`; there is no Store column on that stock-balance table. Therefore, when a specific Store is selected, movement columns remain store-filtered, while stock Before / After / Difference are deliberately shown as **N/A** rather than mixing store movements with location-wide balances.

Stock Before/After history is exact from the Simple Audit stock baseline forward. For earlier dates, movement information can still be shown, but historical stock balances that cannot be reconstructed are shown as N/A.

The legacy `purchase_lines.quantity_returned` field is cumulative. Simple Audit captures future return-quantity deltas from installation onward. For a period before that capture start, the module clearly warns that Purchase Return quantity is the best historical value available from the legacy schema.

## Entry URL

Default route:

`/superadmin/simple-audit/purchase-audit`

The prefix can be changed with `SAU_ROUTE_PREFIX`.

## Permissions

- `simpleaudit.view`
- `simpleaudit.export`
- `simpleaudit.print`
- `simpleaudit.pdf`
- `simpleaudit.email`
- `simpleaudit.whatsapp`

The module supports a host Spatie-compatible permission model or Laravel Gates when present. Super Admin is allowed. The module does not hard-depend on either permission package.

See `INSTALLATION.md` and `VALIDATION.md` before production deployment.

## v1.0.3 verification fix

`simple_audit_verify.sql` is now a fully read-only phpMyAdmin-safe verifier that reads only `INFORMATION_SCHEMA`. Missing `sau_` tables are reported as `MISSING` and a wrong selected database is reported as `WRONG DATABASE`; the verifier no longer directly queries `sau_settings` or other module tables.


### v1.0.3 sidebar fix

Simple Audit must be globally enabled after upload:

```bash
php artisan module:enable SimpleAudit
php artisan optimize:clear
```

The module now includes its own standard sidebar partials so the application's automatic module sidebar can discover and render **Simple Audit → Purchase Audit** without modifying the core sidebar.


## v1.0.4 central system and route bootstrap fix

This release fixes the case where `php artisan module:enable SimpleAudit` reports success but
`php artisan route:list | grep simple-audit` returns no rows. The deployment parcel includes a
small host bootstrap bridge in `app/Providers/AppServiceProvider.php`, the correct `/simple-audit`
registry mapping, and a central Super Admin installed-module catalogue fallback. The module also
publishes `Config/module_permissions.php` so Purchase Audit participates in the system-standard
Manage Page / Role / sidebar contract.

No database SQL re-import is required when upgrading from v1.0.2/v1.0.3.


## v1.0.7 central-to-tenant connection correction

When Simple Audit is opened from Central Super Admin, the module now uses the host application's dedicated tenant database connection template (normally `mysql_tenant`) rather than assuming the central MySQL user can access every tenant schema. Set `SAU_TENANT_CONNECTION` only when your deployment uses a different tenant connection name. No database migration is required for this change.

## v1.0.13 low-memory bootstrap and route verification

The server's existing PHP CLI memory limit is retained. Simple Audit no longer asks deployment staff to run Laravel's global `route:list` command, because this ERP has a very large route collection and that command reflects the whole application before applying the name filter.

Use the module-owned checks instead:

```bash
php Modules/SimpleAudit/Tools/check_sidebar.php
php artisan simple-audit:routes
php artisan simple-audit:diagnose --tenant=100
```

The service provider was also simplified so it no longer scans the full route collection during boot and no longer schedules a second route-loading pass. No SQL/database change is required for v1.0.13.

**v1.0.13 supersedes the older README instructions that used `php artisan route:list` for Simple Audit deployment verification.**
