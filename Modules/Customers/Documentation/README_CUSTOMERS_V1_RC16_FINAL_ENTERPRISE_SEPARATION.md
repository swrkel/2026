# Customers v1.0 RC16 - Final Enterprise Separation Package

Date: 2026-07-01

## Purpose
This package continues the Contacts-to-Customers migration and should be used as the latest baseline after RC15.

## What this RC16 verifies
- Customers module routes are module-owned under `Modules/Customers/Routes`.
- Customers module controllers, services, policies, middleware, views, assets and language files are contained inside `Modules/Customers`.
- Legacy customer URLs are handled through `CustomerCompatibilityController` so old customer bookmarks/AJAX calls can be routed back into Customers.
- Supplier-specific Contacts routes are intentionally left untouched to avoid breaking supplier functionality.

## Important database note
The live ERP schema still uses the `contacts` table and `contact_id` foreign key names in Sales, POS, Payments, Ledgers and other transaction tables. RC16 does not rename those physical database columns. Renaming those columns would be a separate high-risk tenant migration and must be approved separately.

## Runtime dependency audit result
No runtime `Modules\\Contact`, `App\\Contact`, `Contact::`, `ContactController`, `ContactsController`, `contact.customer`, `route('contacts...')`, or `view('contact...')` references were found inside active Customers module PHP/Blade runtime files, excluding documentation/audit files and intentional compatibility scanner patterns.

## Deployment
1. Backup current server files and tenant databases.
2. Replace the included folders/files on the server.
3. Clear Laravel cache/config/view cache.
4. Test Customers menu pages first.
5. Test legacy customer links only after Customers pages load correctly.

## Recommended first test sequence
1. Customers > Dashboard
2. Customers > Register
3. Add Customer
4. Edit/View Customer
5. Customer Ledger
6. Customer Statement
7. Customer Due / Aging / Balance reports
8. Import / Export
9. Sales/POS customer search and payment flows

## Final status
This is a release-candidate baseline for server testing. It is not safe to call production complete until the above screens are tested on the live tenant with real permissions and real data.
