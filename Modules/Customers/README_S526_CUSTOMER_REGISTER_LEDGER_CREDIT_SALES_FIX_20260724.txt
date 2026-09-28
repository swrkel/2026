CUSTOMERS MODULE - S526 CUSTOMER REGISTER / LEDGER CREDIT SALES FIX
Date: 24 July 2026

TEST DOCUMENT
S 526 – Customer Register Page and Ledger – 24 Jul 2026

ISSUE
Credit sales already saved in the transactions table were not appearing in:
- Customers / Customer Register / Total Due
- Customers / Customer Register / Action / Ledger

ROOT CAUSE
The register and ledger switched completely to contact_ledgers whenever that
single table existed. Some sales-producing modules save the sale correctly in
transactions but do not create the matching contact_ledgers row. Those sales
were therefore invisible to the Customers module.

CORRECTION
1. contact_ledgers remains the primary source.
2. Missing credit-sale transactions are added only when no matching
   contact_ledgers.transaction_id exists.
3. Missing linked payments are added only when neither the payment nor its
   parent payment is represented in contact_ledgers.
4. Existing historical sales appear immediately; no data backfill is required.
5. Opening balances are added once only and are not duplicated when an opening
   transaction or ledger row already exists.
6. The same reconciled source is used by Total Due, Ledger, Statement and
   Balance Details actions.
7. Page-sized aggregate queries preserve the earlier instant-loading fix.

CHANGED FILES
- Services/CustomerReceivableService.php                 NEW
- Services/CustomerService.php
- Services/CustomerLedgerService.php
- Providers/CustomersServiceProvider.php
- Database/Migrations/2026_07_24_000002_add_customer_credit_sale_reconciliation_indexes.php
- Database/RawSQL/2026_07_24_customer_credit_sale_reconciliation/*

TENANT DATABASE DEPLOYMENT
Option A - Laravel migration:
php artisan migrate --path=Modules/Customers/Database/Migrations/2026_07_24_000002_add_customer_credit_sale_reconciliation_indexes.php --force

Option B - Raw SQL in each tenant database:
Database/RawSQL/2026_07_24_customer_credit_sale_reconciliation/00_MASTER_CUSTOMER_CREDIT_SALE_RECONCILIATION.sql

The SQL is idempotent and checks table, column, named-index and equivalent-index
existence before applying changes. No INSERT statements are required.

AFTER FILE DEPLOYMENT
php artisan optimize:clear

VALIDATION
- PHP syntax checked for all 329 PHP files in the consolidated Customers module.
- No data-changing backfill is included.
