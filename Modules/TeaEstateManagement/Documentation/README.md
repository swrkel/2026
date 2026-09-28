# Tea Estate Management

Standalone Laravel/Nwidart module for a central-database/single-code, multi-tenant, multi-business ERP.

## Scope
Plantation and fields, field activities/costs, own-estate harvest, independent tea suppliers/buyers, green-leaf buying and supplier payments, factories, processing batches/stages/costs, made-tea inventory and traceability, tea sales/receipts, operational reports, settings, audit trail and Finance ledger integration.

## Independence
All operational data lives in `tea_*` tables. The module does not import PHP classes from Finance, Customers, Suppliers, Purchases, Sales, POS or other functional modules. Common ERP facilities used deliberately are authentication, tenant/business session context, business locations, role permissions, the application layout and the common accounting ledger tables when financial integration is available.

Tea remains the authoritative operational source. The module-owned Finance bridge writes balanced rows to the standard accounting ledger (`accounts`, `transactions`, `account_transactions`) without importing Finance module classes. Missing mappings/posting errors are retained in `tea_finance_events` for controlled retry, preventing operational records from being lost.

## Location rule
Location is mandatory on operational entries. If the user has one authorised location it auto-loads; with multiple authorised locations the user must explicitly select one. Server-side checks prevent posting to an unauthorised location.

## Tenant-safe installation
Run `Database/SQL/TEA_ESTATE_MANAGEMENT_MASTER_INSTALL.sql` in the intended **TENANT database only**. The module provider intentionally does not auto-run operational migrations because a normal central migration must never create Tea operational tables in the central database.

After SQL installation:
1. Extract the module under `Modules/TeaEstateManagement`.
2. Clear Laravel caches.
3. Enable the module for the business and assign the required `tea_estate.*` role permissions.
4. Open Tea Estate Management -> Settings and configure factories/grades. Standard processing stages are seeded on first Settings load if absent.
5. Open Tea Estate Management -> Finance Integration and map the required Finance accounts before live financial posting.

## Finance mappings
Map Green Leaf Inventory, Tea Estate Operating Expense, Estate Production Cost Recovery, Tea Processing WIP, Tea Supplier Payable, Finished Tea Inventory, Tea Accounts Receivable, Tea Sales Revenue, Tax Payable and Cost of Tea Sold to existing Finance accounts. Field costs, processing costs, supplier payments and customer receipts also require the actual Cash/Bank account for the transaction when Finance is available.
