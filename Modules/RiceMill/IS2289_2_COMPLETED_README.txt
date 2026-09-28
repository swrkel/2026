IS2289-2 Rice Mill - Consolidated Updated Parcel - 17 Sep 2026
==============================================================

IMPLEMENTED FROM THE SUPPLIED DOCUMENT
--------------------------------------
1. Rice Mill / Settings
   - Product Category Mapping is the FIRST Settings tab.
   - Separate Paddy and Rice category mapping.
   - Categories come from the shared Products/Product-New category master through a Rice Mill adapter.
   - Search/type-to-filter dropdowns with scrolling.
   - Current selections stay visible on the same page.
   - Paddy and Rice Payment Account dropdowns show List Accounts linked to the Current Liabilities Account Group.
   - Mapping values are stored in rcm_settings.settings JSON.

2. Rice Mill / Settings / Material Usage Mapping
   - Added as a Settings tab.
   - Uses the existing standalone packaging-material mapping and PackingService consumption engine.
   - Packing automatically reduces mapped material quantities and records material movements.

3. Paddy Purchase renamed to Purchase Order in the Rice Mill UI
   - Sidebar/List/Create/Receive Paddy references/Dashboard/Settings/report labels updated.
   - Internal route/database names retained for backward compatibility.

4. Purchase Order / Purchase Payment - UPDATED STANDARD FLOW
   - Payment Method uses purchase-enabled/mapped methods.
   - Payment Account is filtered by selected Payment Method.
   - Credit Purchase (Due) uses the mapped Paddy Current Liabilities account.
   - Cheque Number is retained when Cheque is selected.
   - The Rice Mill module NO LONGER inserts standalone debit/credit account_transactions.
   - It creates a linked standard Purchase transaction (status=ordered, sub_type=rice_mill_purchase_order) and saves payment through TransactionUtil::createOrUpdatePaymentLines(), the same payment engine used by the core Purchase module.
   - Standard transaction_payments/payment status/account events therefore remain in the normal ERP chain.
   - rcm_purchase_payments is only the Rice Mill audit/link table and stores transaction_id / transaction_payment_id.
   - Rice Mill approval does NOT queue a second supplier-payable Finance event because approval is still only a Purchase Order stage.

5. Purchase Tax - Purchase Order stage
   - Purchase Tax dropdown added from the business tax_rates master.
   - Selected tax ID/rate is retained on the Rice Mill Purchase Order.
   - purchase_tax_amount remains 0.0000 at Purchase Order stage.
   - Tax is NOT separately added to the PO total.
   - Tax is NOT posted to tax ledgers/accounts at Purchase Order stage.
   - Actual tax calculation/posting is deferred until the future actual purchase/receipt stage.

DATABASE
--------
If previous IS2289-2 SQL was already imported:
- Import Database/SQL/RiceMill_IS2289_2_Standard_Purchase_Flow_Tax_17Sep2026.sql

If previous IS2289-2 SQL was NOT imported:
- Import Database/SQL/RiceMill_IS2289_2_Consolidated_17Sep2026.sql

Fresh installations:
- Use updated Database/SQL/RiceMill_Master_SQL.sql
OR
- run Rice Mill migrations through 2026_09_17_000010.

DEPLOYMENT
----------
1. Back up Modules/RiceMill and tenant DB.
2. Replace the supplied RiceMill folder.
3. Import the applicable SQL into each tenant DB.
4. Run: php artisan optimize:clear
5. Verify Settings > Product Category Mapping and Paddy Current Liabilities account mapping.
6. Test Cash/Bank/Cheque and Credit Purchase (Due).
7. Select a Purchase Tax and confirm PO total is unchanged and no tax ledger is posted.
