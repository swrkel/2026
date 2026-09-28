# Customers V1 RC17 - Release Hardening

Date: 2026-07-01

## Purpose

RC17 continues the Customers standalone migration from RC16 and focuses on release hardening and audit clarity before live server testing.

## What was verified in this pass

- The RC16 baseline package was unpacked and inspected.
- The Customers module exists as a full module under `Modules/Customers`.
- The active Customers module contains dedicated controllers, services, repositories, middleware, routes, views, exports, imports, SQL support scripts and documentation.
- Runtime references to legacy Contact controllers/views inside `Modules/Customers` were scanned.

## Audit result

No active Customers module runtime dependency was found for these high-risk patterns, except the intentional audit scanner file and documentation notes:

- `Modules\\Contact`
- `App\\Contact`
- `Contact::`
- `ContactController`
- `ContactsController`
- `contact.customer`
- `route('contacts...')`
- `view('contact...')`

## Important schema note

The live ERP still uses the physical `contacts` table and `contact_id` columns in transactions, ledgers, payments, sales, POS and related tenant tables. RC17 does not rename those database structures. That schema rename would be a separate high-risk migration and should not be mixed with this module migration.

## Compatibility note

Some `/contacts/...` routes intentionally remain in the main route files for supplier flows, non-customer contact flows, and backward compatibility. Customer-facing compatibility endpoints are routed to `Modules\\Customers\\Http\\Controllers\\CustomerCompatibilityController` where appropriate.

## Recommended server testing order

1. Sidebar: Customers module visible and Contacts customer menu hidden/redirected.
2. Customers Dashboard.
3. Customer Register list.
4. Add Customer.
5. Edit Customer.
6. View/Profile page.
7. Customer Ledger report.
8. Customer Statement report.
9. Customer Balance/Due report.
10. Customer Payments report.
11. Import Customers.
12. Export Customers.
13. Legacy `/contacts/customers` URL redirects/loads through Customers compatibility.
14. Sales/POS customer lookup still works.
15. Customer payment and statement old links do not break existing data.

## Status

RC17 is a release-hardening handoff package. It is ready for server-side testing. Do not declare production completion until the above live checks pass on the tenant site.
