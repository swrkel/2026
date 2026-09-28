# Customers v1 RC18 - Production Release Candidate

## Purpose
RC18 continues the Contacts → Customers migration using the latest RC17 baseline. It is intended as a production release candidate for server validation.

## What this package contains
- Full `Modules/Customers` module from the latest baseline.
- Updated root `routes/web.php` and `routes/tenant.php` compatibility routes where present in the baseline.
- Module documentation and dependency scan notes.

## RC18 audit result
The active Customers runtime files were scanned for direct runtime dependency patterns on the old Contacts customer pages/controllers:

- `Modules\\Contact`
- `App\\Contact`
- `Contact::`
- `ContactController`
- `ContactsController`
- `contact.customer`
- `route('contacts...')` / `route("contacts...")`
- `view('contacts...')` / `view("contacts...")`

The remaining matches are expected compatibility/schema references, mainly:

1. Physical database schema names such as `contacts`, `contact_id`, and `contact_ledgers`.
2. Compatibility routes that keep old `/contacts/...` customer URLs working while redirecting/serving them through `Modules\Customers\Http\Controllers\CustomerCompatibilityController`.
3. Audit/documentation files that intentionally mention the old patterns.

## Important note about database schema
The live ERP still uses the `contacts` table and `contact_id` columns in Sales, POS, Payments, Ledgers, Accounting and many tenant transaction tables. RC18 does not rename those physical database structures. That rename must be a separate approved tenant migration because it is high-risk and affects many modules.

## Deployment recommendation
Use this package as the latest Customers v1 production release candidate for server testing. Test in this order:

1. Customers sidebar/menu visibility.
2. Customer register page.
3. Add/Edit/View customer.
4. Customer groups.
5. Customer ledger.
6. Customer statement.
7. Customer due/balance reports.
8. Import/export.
9. Sales/POS customer selection.
10. Customer payments and advance payments.
11. Old `/contacts/...` customer URL compatibility.
12. Multi-tenant/business-location permission visibility.

## Completion status
Architectural migration is ready for server-side validation. Do not remove/rename tenant database columns until all dependent ERP modules are migrated and a separate database migration plan is approved.
