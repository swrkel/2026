# Audit Module Changelog

## 1.0.20 - 2026-09-11
- Fixed Central Audit tenant resolution where tenant rows could be listed from the central `tenants` table but `find()` could not resolve the same visible tenant IDs.
- Tenant resolution now first matches against the already-working central tenant collection, then falls back to conventional primary-key and `id` lookups, then domain resolution.
- Central source discovery now explicitly restores the central database before enumerating tenants/domains so a previous failed tenant probe cannot contaminate later scope/filter calls.
- Keeps the v1.0.19 central PDO/config restoration safety fix.
- No database/schema change and no operational ERP data changes.


## 1.0.19 - 2026-09-11
- Fixed central Audit HTTP 500 after probing an inaccessible tenant database.
- Central source switching now restores the original central/default and mysql database configuration in an outer `finally` block, including when `tenancy()->initialize()` throws.
- Purges restored Laravel connections so stale tenant PDO handles cannot leak into central layout/auth/sidebar queries.
- Central database labels now use the request-start central database snapshot instead of a tenant-mutated runtime value.
- No schema or SQL changes.

# Changelog

## 1.0.14 - 2026-09-11
- Rebuilt the Audit Rules page to match the professional styling of the other Audit pages.
- Added compact rule summary cards, search and module filtering.
- Replaced the overflow-prone Rules table with a fixed-layout desktop table whose columns wrap inside the Audit viewport.
- Added responsive card-style Rules rows for smaller screens so the page never requires horizontal page scrolling.
- Added professional Enabled/Disabled switches and compact Save controls without changing rule-setting behavior.
- Added CSS/JS cache-busting at `v=1.0.14`.
- No Audit engine logic, SQL/schema, or operational ERP data changes.

## 1.0.13 - 2026-09-11
- Fixed browser/business-scoped audit failures caused by applying physical table names after SQL aliases were introduced.
- `scopeContext()` now separates schema table names from SQL query qualifiers/aliases.
- Corrected alias-aware business/location scoping for FIN-ACC-001, CUS-CON-001, SUP-CON-001 and Inventory orphan-location checks.
- Customer validation now recognises both `customer` and `both` contact types, matching the ERP customer model.
- When a rule previously failed safely and later completes successfully, its old Audit-engine warning is automatically marked resolved with history.
- No operational ERP data is modified. No database schema change.


## 1.0.10 - 2026-09-11
- Reclassified all recognisable v1.0.7/v1.0.8 FIN-BAL-001 and old XMOD-PAY-001 false positives as `false_positive` in Audit history only.
- Dashboard now shows current actionable findings instead of cumulative historical false positives.
- Added Latest Run finding count and per-run finding counts to Recent Audit Runs.
- Findings by Module and Current Findings now exclude resolved, ignored and false-positive history.
- Dashboard search now filters finding no, rule, module, title and message.
- Improved Audit filter row sizing so Business, Location and Apply stay on one row on normal desktop widths.
- Added active Audit navigation tab styling matching the ERP tab standard.
- No database schema changes and no operational ERP data changes.

# Audit Module - Consolidated Build 2026-09-11

## Foundation
- Standalone module provider, routes, models, controllers, services, rules, views, assets, language, permissions and reports.
- `audit_` database prefix throughout.
- Multi-business/location context and active tenant connection awareness.

## Audit engine
- Safe schema checks and per-rule exception isolation.
- Duplicate finding suppression with deterministic fingerprints.
- Reopening of previously resolved findings when the same problem is detected again.
- Manual resolution/status history; no silent operational data repair.

## Initial module rules
- Finance, Customers, Suppliers, Inventory, User Management, System and Cross-Module rules.

## UI/reporting
- POS-dashboard-inspired responsive UI.
- This Year, Last Year, This FY, Last FY and Custom report presets.
- Business/location/module/severity/status filters.
- CSV, Excel-compatible `.xls`, PDF when DomPDF is available, printable fallback, Print and Column Visibility.

## Deployment
- Laravel migrations + full Master SQL.
- Permission seeder + permission/menu manifests.
- Public assets included plus optional `audit-assets` publish tag.

## 2026-09-11 - Loader compatibility fix 1.0.1
- Added dedicated `Providers/RouteServiceProvider.php`.
- Main `AuditServiceProvider` now registers the route provider using the same pattern as established ERP modules.
- `module.json` explicitly registers both providers.
- Added enabled-module `bootstrap.php` compatibility guard; no global/core file edits required.
- Route registration is guarded against duplicates.

## 1.0.4 - 2026-09-11
- Register Audit web routes from the main provider in an application `booted()` callback.
- Remove redundant dedicated RouteServiceProvider registration from module.json/bootstrap.
- Keep all route registration module-local; no global/core route edits required.

## 1.0.6 - 2026-09-11
- Route registration moved to Nwidart module-local `bootstrap.php` declared by `module.json`.
- Removed dependency on module RouteServiceProvider lifecycle.
- Added tenant-safe middleware defaults matching this ERP's standalone tenant route pattern.
- Added read-only `php artisan audit:diagnose` command for one-step deployment validation.
## 1.0.7 - 2026-09-11
- Made `audit:run` tenant-explicit for CLI. It refuses to silently audit the central connection and accepts `--tenant=<tenant-id-or-domain>`.
- Browser-triggered audits now record the active Stancl tenant key and database in Audit context.
- Reworked `SYS-ROUTE-001` so it never autoloads every module controller; controller files are inspected statically with route/finding caps.
- Added per-rule progress telemetry (`current_rule`, `last_rule`, `progress_status`, `progress_at`) to `audit_runs.context`.
- Added stale `running` run recovery and a best-effort fatal shutdown guard so interrupted runs do not remain indefinitely active.
- Made `audit:scheduled` tenant-aware with `--tenant` and `--all-tenants`; optional scheduler uses `--all-tenants`.
- Extended `audit:diagnose` with tenancy initialization/current tenant/current database information.
- No database schema change is required from v1.0.6.


## 1.0.8 - 2026-09-11
- Corrected XMOD-PAY-001 so legitimate direct customer/advance payments with NULL transaction_id are not reported as orphan transactions.
- Corrected FIN-BAL-001 to evaluate only active account_transactions belonging to active parent transactions; soft-deleted/historical ledger rows and soft-deleted parent transactions are excluded from live imbalance detection.
- Preserved read-only behavior against operational ERP data. No schema change.

## 1.0.9 - 2026-09-11
- Reworked `FIN-BAL-001` after live tenant validation showed that `account_transactions.transaction_id` is a source/business link, not a guaranteed self-contained double-entry journal group.
- `FIN-BAL-001` now audits only explicit counterpart relationships declared by `pair_at_id` and validates: counterpart exists, counterpart is active, debit/credit sides are opposite, amounts match within tolerance, and business IDs match when available.
- Normal one-sided source postings such as opening stock, stock adjustment and settlement/credit-sale ledger entries are no longer misclassified as critical imbalances solely because the same `transaction_id` has no opposite row.
- Preserved read-only behavior against operational ERP data. No schema change.
- Added best-effort reconciliation for old open/reopened findings created by the superseded FIN-BAL-001 transaction-group assumption and the old NULL-payment XMOD-PAY-001 assumption. Only Audit-owned finding/status/history tables are updated; operational ERP data remains untouched.

## 1.0.11 - 2026-09-11
- Dashboard display logic unchanged from v1.0.10; current findings and latest-run counts remain authoritative.
- Added a desktop left gutter so the ERP floating sidebar toggle no longer covers Audit cards, labels, tables, or filters while scrolling.
- Added explicit Select2/enhanced-select sizing for Business, Location and Date Preset filters so generated selector containers cannot expand to full width and force Location/Apply onto separate rows on normal desktop screens.
- Kept responsive wrapping for smaller screens.
- No SQL/schema change and no operational ERP data changes.

## 1.0.12 - 2026-09-11
- Rebuilt the dashboard filter toolbar around stable wrapper/grid cells so global Select2/bootstrap-select width rules cannot push Location and Apply onto separate rows.
- Kept Search, date preset, Business, Location and Apply on one compact desktop row; Custom dates are inserted on the same toolbar when selected.
- Added a module-local desktop content gutter using margin/width (not only padding) so the ERP floating sidebar toggle no longer covers the first Audit card.
- Added asset cache-busting (`?v=1.0.12`) for Audit CSS/JS so deployments do not keep an older toolbar stylesheet in the browser/proxy cache.
- Audit rules, findings logic and database schema are unchanged from v1.0.11.

## 1.0.15 - 2026-09-11
- Rebuilt Audit Schedules page to match the professional Audit UI standard and remain inside the screen width.
- Corrected scheduler instructions for multi-tenant execution: `audit:scheduled --all-tenants` and tenant-specific testing.
- Audit automatic scheduler registration is enabled by default and can be disabled with `AUDIT_SCHEDULED_ENABLED=false`.
- Added schedule summary cards, automatic-runner status, frequency guidance, responsive saved-schedule table and current-business schedule scoping.
- Schedule creation now requires at least one valid Audit module; arbitrary module values are discarded.
- Schedule toggle now enforces current-business ownership.
- One failed scheduled Audit no longer prevents later due schedules in the same tenant from being checked.
- No SQL/schema change and no operational ERP write behavior was added.

## 1.0.16 - 2026-09-11
- Rebuilt Audit Reports presentation to match the professional Audit module design baseline.
- Added report summary cards for total rows, current/open, resolved and false positives.
- Fixed the main report Search filter so it now filters the server-side report query and exports.
- Business and Location columns now display names instead of raw numeric IDs, including exports/print/PDF.
- Last Seen now uses a readable `d M Y H:i` display instead of the raw database timestamp.
- Added severity/status badges, wrapped issue text and controlled report column widths.
- PDF output is landscape for the wide audit report.
- No database schema or operational ERP data changes.

## 1.0.17 - 2026-09-11
- Fixed Reports -> PDF opening the printable HTML page when DomPDF is unavailable.
- PDF now always returns a real downloadable landscape PDF.
- Uses Barryvdh DomPDF when available, raw dompdf/dompdf when available, then a module-owned standalone PDF renderer as a guaranteed fallback.
- Removed `target=_blank` from the PDF export button so the browser receives the attachment directly.
- No database/schema changes.

## 1.0.18 - 2026-09-11
- Added Central Multi-Tenant Audit on configured central domains while preserving the existing tenant Audit routes and pages unchanged.
- Central `/audit` can now inspect the central database plus every configured Stancl tenant database, opening tenant databases one at a time for isolation and safe failure handling.
- Added searchable multi-select Tenant/Data Source, Business and Location scope controls with dependent Business/Location loading.
- Blank Tenant/Data Source means All central + tenant databases; selected businesses/locations restrict both central reports and central Audit execution to the selected scope.
- Added Central Dashboard with per-database readiness, business/location counts, latest run status and current findings.
- Added Central Run Audit orchestration with one database at a time, job safety cap, safe skips for inaccessible/missing Audit tables and database-wide System checks deduplicated per selected source.
- Added Central Findings and Central Reports aggregation with Tenant/Data Source, Database, Business and Location columns and working CSV/Excel/PDF/Print/Column Visibility exports.
- Added read-only Central Rule Catalog and Central Schedule Overview.
- Audit finding creation can now carry rule-derived business/location scope even when the parent Audit run is database-wide.
- Updated compatible Finance, Customer, Supplier, Inventory, Cross-Module and User Management rules to preserve/derive row-level business/location context.
- Central views are domain-guarded and continue to use the Audit permission layer. Tenant-domain Audit routing remains tenant-initialized and unchanged.
- No database schema change. No operational ERP table writes were added; central execution writes only to each selected database's `audit_*` tables.
