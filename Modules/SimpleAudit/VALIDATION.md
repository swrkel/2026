# Simple Audit — Validation Notes

## Validation completed for this parcel

- Checked against the supplied `nivasa_template` schema dated 20 Sep 2026.
- Confirmed source tables used by Purchase Audit exist in the supplied template, including `business`, `business_locations`, `stores`, `transactions`, `purchase_lines`, `stock_adjustment_lines`, `transaction_payments`, `accounts`, `account_transactions`, `contacts`, `contact_ledgers`, `products`, `variations`, `categories` and `variation_location_details`.
- Confirmed the supplied stock balance source `variation_location_details` is Location-level and has no Store column; the module therefore avoids presenting false Store-level Before/After stock balances.
- Confirmed all five module-owned tables are prefixed `sau_`.
- Confirmed the tenant SQL defines 21 `sau_` triggers and 21 corresponding safe `DROP TRIGGER IF EXISTS` statements before recreation.
- PHP syntax validation passed for all module PHP files used in this build.
- JavaScript syntax validation passed for `Resources/assets/js/simple-audit.js`.
- SQL installation file delimiter structure and module object counts were statically checked.

## What still requires deployment-environment testing

Static validation cannot reproduce the live application's Laravel version, module loader, authentication/permission package, mail configuration, exact central tenant connection settings or live tenant data. Before rolling out to every tenant, install on one test tenant and validate the checklist below.

## Production test checklist

- Simple Audit appears in the expected module/sidebar registry.
- Central Super Admin can select Tenant → Business → Location and data switches to the correct tenant DB.
- A normal tenant user cannot switch into another tenant.
- Business and Location access rules are respected.
- The first assigned Location auto-loads and remains changeable.
- Store filtering works for movement columns; Store-specific stock Before/After is shown as N/A, with the explanatory warning.
- This Year, Last Year, This FY, Last FY and Custom date ranges work.
- Purchase totals agree with source purchases for the period.
- Stock purchases, purchase returns and stock adjustments agree with source rows.
- Supplier Payment rows include only affected suppliers.
- Accounts include only Purchase / Purchase Return / Stock Adjustment / Supplier Payment effects.
- Supplier Ledgers include only suppliers affected by those audit sources while Before/After reflects the actual full ledger balance.
- Hover preview and click popup show transaction detail.
- Currency values use Business Currency Precision; quantities use Quantity Precision; Fuel/Petro category quantities use 3 decimals.
- Excel, CSV, Print, PDF, Email and WhatsApp outputs use the same selected date range and number formatting.
- Report share links expire as configured.
- `php artisan simple-audit:diagnose --tenant=<TENANT_ID>` reports the required module tables.
- `Database/Sql/simple_audit_verify.sql` shows all five `sau_` tables and all expected triggers.

## Historical-data note

No software added today can recreate historical stock snapshots that were never stored by the source application. This module therefore starts reliable Before/After snapshot history at installation and makes unavailable older balances explicit instead of manufacturing values.

## v1.0.1 phpMyAdmin SQL safety correction

- `simple_audit_tenant_install.sql` now preflights the selected database and rejects MySQL/MariaDB system schemas plus databases missing the required ERP tenant tables.
- `simple_audit_verify.sql` no longer directly selects from a missing `sau_settings` table; it reports `WRONG DATABASE`, `MISSING`, or `SKIPPED` safely.
- Added `Database/Sql/READ_ME_FIRST.txt` with the exact phpMyAdmin import order.
