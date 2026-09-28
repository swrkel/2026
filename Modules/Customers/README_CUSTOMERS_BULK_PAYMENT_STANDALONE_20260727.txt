CUSTOMERS MODULE - STANDALONE BULK PAYMENT - 27 JULY 2026
============================================================

New page
--------
/customers/bulk-payment
Route: customers.bulk_payment.index

Implemented functions
---------------------
1. Customer type-and-search dropdown (Select2 enhancement when globally available; native fallback included).
2. Live customer Total Due and customer points.
3. Automatic CPB payment reference.
4. Payment method/account group selection.
5. Customers-owned account loading endpoint. No /accounting-module AJAX route is used.
6. Cash, Card, Cheque and Bank Transfer field handling.
7. Duplicate cheque validation.
8. Transaction Date, Daily Shift No, post-dated cheque controls and notes.
9. Outstanding invoice loading from the tenant database.
10. Multi-invoice selection and allocation.
11. Pay All / Select All.
12. Optional per-invoice customer interest.
13. Live Payment / Allocated / Unallocated summary.
14. Unallocated value is retained as customer advance.
15. Invoice payment status recalculation.
16. Customer ledger posting.
17. Payment account, Accounts Receivable and Interest Income posting.
18. Duplicate reference protection and database transaction rollback.
19. Success message and printable receipt.
20. Existing /customers/customer-payment-bulk compatibility URL retained.

Standalone ownership
--------------------
All new controllers, service logic, routes, views, CSS and JavaScript are inside Modules/Customers.
The new implementation does not import App models/utilities and does not call Contact, Accounting, Petro or other module controllers/views/assets.
It reads/writes the existing shared tenant database tables directly through Laravel DB and Customers SchemaCache.

Database
--------
No migration or raw SQL is required. Existing tenant tables are used.

Deployment
----------
1. Overwrite the supplied Customers files.
2. Run: php artisan optimize:clear
3. Open: /customers/bulk-payment
4. Ensure the existing Customers page switch "Bulk Payment" is enabled in Super Admin > Manage Side Bar / Customers Module.
