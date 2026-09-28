# Egg Management Module

Standalone Laravel module for egg production, grading, stock, purchases, sales, transfers, adjustments, sharing and reports.

## Isolation rules
- All module-owned tenant tables begin with `egg_`.
- No foreign keys are created to Customers, Suppliers, Finance, Products New, Locations or Stores.
- Common modules are reached only through classes under `Integrations/`.
- Finance is integrated through `egg_integration_outbox`; no Egg controller writes directly into Finance tables.
- The active tenant connection is inherited from the application's existing tenancy layer by default (`egg.connection = null`).
- Every operational transaction carries `business_id`, and location/store IDs where relevant.

## Main workflow
1. Configure Egg Grades and Layer Flocks.
2. Record Daily Egg Collection.
3. Grade and pack good eggs; this creates FIFO stock lots.
4. Add purchased eggs if applicable.
5. Transfer stock between locations/stores.
6. Sell eggs to Customers.
7. Record stock adjustments/wastage.
8. Use reports and secure share links for Print / SMS / Email / WhatsApp.

## Installation
1. Copy `EggManagement` into `/Modules/EggManagement`.
2. Ensure the module is enabled by the application's module loader.
3. Run `composer dump-autoload` if your project does not auto-discover module PSR-4 classes.
4. Run `php artisan migrate` on each tenant using the existing tenancy migration process, OR import `Database/SQL/EGG_MASTER_TENANT_SCHEMA.sql` into each tenant DB.
5. Publish assets: `php artisan vendor:publish --tag=egg-assets --force`.
6. Clear caches: `php artisan optimize:clear`.
7. Verify installation: `php artisan egg:health`.
8. Optional standalone permission bootstrap: `php artisan egg:grant-user BUSINESS_ID USER_ID`.
9. Configure grades from Egg Management > Configuration, or edit/run `EGG_DEFAULT_GRADES_TEMPLATE.sql`.
10. Register permissions in your User Management / Manage Page importer using `Permissions/permissions.php`. The module also supports `egg_access_grants` as a standalone fallback.

## Common module integration
- Customers: `Integrations/CustomerGateway.php`
- Suppliers: `Integrations/SupplierGateway.php`
- Products New: `Integrations/ProductGateway.php`
- Locations / Stores: `Integrations/LocationStoreGateway.php`
- Finance: `Integrations/FinanceGateway.php` + `egg_integration_outbox`
- Email/SMS/WhatsApp: `Integrations/MessagingGateway.php`

If a shared table name differs in a deployment, change only `Config/config.php` or the relevant gateway; do not modify transaction controllers.

## Safe Finance bridge
The module deliberately does not guess the schema of Finance/accounting tables. Every approved Sale, Purchase and Adjustment creates an outbox event. Your Finance bridge should process the event once and then set its outbox row to `processed`. This prevents duplicate postings and keeps Egg Management upgrade-safe.
