# Customers V1 RC20 - Final Server Validation Baseline

Date: 2026-07-01

## Purpose
RC20 is the server-validation baseline after RC19. It keeps the Customers module as the owner of all customer pages, reports, compatibility redirects, menus, and module-level services.

## Important honest audit note
The module still uses the existing ERP `contacts` table for the customer master data (`type = customer` / `type = both`). This is intentional for this release candidate because live Sales, POS, Payments, Accounting, Ledger, and historical transaction tables still use `contact_id` and `contacts.id` as the production relationship.

So RC20 is standalone at the application/module layer, but it is not a separate physical customer-master database table release. A separate table migration should only be done as a later data-migration project after all dependent ERP modules are refactored.

## What to test on server
1. Customers sidebar visibility.
2. Customers Dashboard.
3. Customer Register.
4. Add Customer.
5. Edit Customer.
6. View/Profile Customer.
7. Customer Groups.
8. Customer Ledger.
9. Customer Statement.
10. Customer Balance / Due reports.
11. Customer Payments.
12. Customer Import / Export.
13. Old Contacts customer links redirect into Customers.
14. Sales/POS/customer dropdowns continue to find customers.
15. Permission toggles show/hide Customers menu correctly.

## If Contacts customer pages are disabled
Disable only the customer-facing menu/routes from Contacts first. Do not drop or rename the `contacts` table yet because transaction history still depends on it.

## Replacement scope
Replace the packaged folders/files exactly as supplied:
- `Modules/Customers`
- `routes/web.php`
- `routes/tenant.php`
- included `resources` / `public` files if present

Clear Laravel cache after upload:
- route cache
- config cache
- view cache

