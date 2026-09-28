# Audit Module

Standalone Laravel ERP audit, reconciliation and exception-management module.

## Safety principles
- Does not edit Finance, Customer, Supplier, Inventory, User or other operational tables.
- All findings/history/settings are stored only in `audit_` tables.
- Every rule checks table/column availability and skips when the tenant schema is incompatible.
- Rules are small independent classes grouped by source module.
- Multi-business/location context is carried into runs and findings.

## Included first-stage audit rules
- System: Audit schema integrity; loaded module route/controller action integrity.
- Cross Module: orphan `transaction_payments` -> `transactions`.
- Finance: missing account references; debit/credit group balance where compatible ledger columns exist.
- Customers: sale transaction contact integrity.
- Suppliers: purchase transaction contact integrity.
- Inventory: negative location stock; orphan variation; orphan location stock row.
- User Management: orphan role assignment; inactive user retaining role when compatible status columns exist.

## URLs
- `/audit`
- `/audit/run`
- `/audit/findings`
- `/audit/rules`
- `/audit/schedules`
- `/audit/reports`

## Commands
- `php artisan audit:run --tenant=<tenant-id-or-domain>`
- `php artisan audit:run --tenant=<tenant-id-or-domain> --business=3 --module=Finance`
- `php artisan audit:scheduled --tenant=<tenant-id-or-domain>`
- `php artisan audit:scheduled --all-tenants`

See `INSTALL.md` before deployment.

### v1.0.11 UI note
Desktop Audit pages reserve space for the ERP floating sidebar toggle and explicitly size enhanced Business/Location filters so the dashboard controls remain compact and readable. No database change is required from v1.0.10.

### v1.0.12 UI note
The Audit filter toolbar uses wrapper-based CSS grid cells and versioned module assets. This avoids ERP/global select plugins forcing the Location selector or Apply button onto separate rows and reserves a safe desktop gutter for the floating sidebar toggle.

## Central Multi-Tenant Audit (v1.0.18)
When `/audit` is opened from a configured central domain, Audit switches to the Central Audit interface. The same `/audit` URL on tenant domains continues to use the original tenant-only interface.

Central scope can include:
- the central application database;
- one, several or all configured Stancl tenant databases;
- one, several or all businesses inside the selected data sources;
- one, several or all business locations inside the selected businesses/data sources.

The Tenant/Data Source, Business and Location controls are searchable multi-select lists. Leave Tenant/Data Source blank for **All**. For a targeted audit, select source(s) first; the module then loads the businesses and locations belonging only to those sources.

Central execution is isolated: the module initializes one tenant at a time, runs Audit against that database, closes the tenant context, then moves to the next source. One inaccessible tenant does not stop the remaining sources. Operational ERP records are never copied to the central database and are not edited by Audit.

Central reports add Tenant/Data Source and Database columns and preserve the existing CSV, Excel, PDF, Print and Column Visibility tools.

## v1.0.19 central safety fix

Central multi-tenant collection now guarantees that tenant connection changes cannot leak into the central request. Inaccessible tenant databases are reported/skipped while the central auth/layout connection is restored.

## v1.0.20 central tenant resolution fix
Central Audit resolves tenant IDs against the central tenant collection before falling back to model primary-key/domain lookups. Central source discovery also resets to the central connection before enumerating sources. No SQL change is required.
