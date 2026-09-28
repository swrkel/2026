CUSTOMERS MODULE - CUSTOMER REGISTER ACTION SPEED FIX
Date: 24 July 2026

Changed behavior
----------------
1. Customer Register > Action > Ledger
   - Removed the second full ledger-row load (previously up to 10,000 joined rows).
   - Ledger totals now use a single SQL aggregate result.
   - The visible 500 ledger rows no longer join the contacts table unnecessarily.

2. Payment / Advance / Loan / Refund / Deposit / Cheque Return popups
   - Payment accounts are no longer preloaded for every enabled payment method.
   - The popup opens with only the required initial data.
   - Account options load after the user selects a payment method.
   - Payment settings and schema capabilities are cached for the current request.

3. Other Customer Register action pages
   - Permission and subscription checks share one request-scoped cache.
   - Tenant-aware database schema checks are cached per request.
   - Balance Details uses aggregate totals instead of loading every ledger row.
   - Audit Trail no longer queries customer activities twice.

Deployment
----------
1. Overwrite the included files under Modules/Customers.
2. Run the new migration for each applicable tenant database using the project's
   normal multi-tenant migration process.
3. Clear Laravel caches:

   php artisan optimize:clear

The code improvements work without the new indexes, but the migration adds
supporting indexes for consistently fast loading on large tenant databases.
