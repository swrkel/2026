# Rice Mill Module - Installation

This package is intentionally standalone under `Modules/RiceMill`.

## Recommended deployment order
1. Take a full application and tenant database backup.
2. Extract Parcel 1, then Parcel 2, then Parcel 3 into the Laravel project root.
3. For each tenant database, either run the Laravel migration or import `Modules/RiceMill/Database/SQL/RiceMill_Master_SQL.sql` once. Do not do both on the same fresh tenant unless your migration tracking is reconciled.
4. Public CSS/JS is already included in this consolidated package. The module layout also has a module-owned fallback, so the page will not become unstyled if assets were not published. `php artisan vendor:publish --tag=ricemill-assets --force` remains optional for future asset refreshes.
5. Clear caches: `php artisan optimize:clear`.
6. Sync permissions into the required tenant database: `php artisan rcm:sync-permissions --tenant=<TENANT_UID>`. If the command is already running inside an initialized tenant request/console context, the option may be omitted.
7. Confirm routes: `php artisan route:list | grep rice-mill`.
8. Enable **Rice Mill Module** in Super Admin / Manage Sidebar and assign the desired Rice Mill permissions in User Management New.

## Tenant safety
All Rice Mill web routes include the host `tenant.context` middleware before module access. Operational queries then require `business_id`, so the boundary is Domain → Tenant UID → tenant database → Business UID → permitted Location/Store. The module does not switch to another tenant from user input.

## Integrations
- Customers and Suppliers are read through the common `contacts` table only when that table exists.
- User Management permissions are exposed by `Config/permissions.php` and `php artisan rcm:sync-permissions --tenant=<TENANT_UID>`. Purchase Approval is a separate `rice_mill.paddy_purchase.approve` permission.
- Finance posting is isolated through `rcm_finance_outbox` and the `RiceMillFinancialTransactionReady` event. To directly post into the installed Finance Module, configure `RCM_FINANCE_HANDLER` with a class implementing `handleRiceMillTransaction(array $outboxRow)`.
- The module deliberately does not write directly into unknown Finance ledger schemas. This prevents Rice Mill from corrupting an existing Finance implementation.

## Sidebar
`Config/sidebar.php` remains the Rice Mill menu manifest and the actual submenu stays in `Modules/RiceMill/Resources/views/layouts_v2/partials/sidebar.blade.php`.

On this ERP build, the generic `automatic-module-sidebar` renderer may show only one flat module link and may not render a module-owned child menu. Install the safe one-line host hook once:

`php artisan rcm:install-sidebar-hook`

The command is idempotent, backs up any shared sidebar file it changes under `storage/app/ricemill-backups`, and inserts only the Rice Mill `@includeIf(...)` before the existing automatic-module include. It does not replace the shared sidebar. Afterward run `php artisan optimize:clear`.

Rollback, if ever required: `php artisan rcm:install-sidebar-hook --remove`.

## First setup in the UI
Open `/rice-mill/settings` and add paddy varieties, mills, and finished rice products before entering receiving/production transactions.

## v16 - Controlled Receive Paddy Numbering
Rice Mill > Settings > Receive Paddy now includes an **Edit Numbering** popup for the next Weighbridge, Paddy Receipt and Paddy Purchase numbers. Users enter only the numeric part; the PD-WB-, PD-RCV- and PD-PUR- prefixes remain fixed. The controller locks the number-series rows, checks the highest already-used transaction number for the current business, and refuses any next number below the safe minimum. Historical documents are not renumbered. No database schema change is required.
