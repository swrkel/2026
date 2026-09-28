Rice Mill - IS2289-2 Follow-up
17 Sep 2026

PURCHASE ORDER PAYMENT ALIGNMENT
--------------------------------
1. Rice Mill no longer inserts standalone account_transactions for Purchase Order payments.
2. The Rice Mill Purchase Order remains the operational rcm_paddy_purchases record.
3. A linked ERP Purchase transaction is created with status = ordered and sub_type = rice_mill_purchase_order.
4. Payment is saved through App\Utils\TransactionUtil::createOrUpdatePaymentLines(), the same standard path used by the core Purchase module.
5. Standard transaction_payments / payment status / account event handling is therefore preserved.
6. Credit Purchase (Due) is supported and remains Due; the mapped Paddy Current Liabilities account is shown as its Payment Account.
7. rcm_purchase_payments is now a Rice Mill audit/link table and stores the linked transaction_id and transaction_payment_id. It no longer creates debit/credit account rows itself.
8. Rice Mill Purchase Order approval no longer queues a second supplier-payable Finance event. Purchase Order approval is not actual receipt/purchase recognition.

PURCHASE TAX AT PURCHASE ORDER STAGE
------------------------------------
1. A Purchase Tax dropdown is available on the Purchase Order.
2. Tax options come from the business tax_rates master.
3. The selected tax ID/rate is retained on rcm_paddy_purchases.
4. purchase_tax_amount is intentionally 0.0000 at Purchase Order stage.
5. Tax is NOT separately added to the Purchase Order total.
6. Tax is NOT posted to Taxes Receivable/Payable or any tax ledger at Purchase Order stage.
7. The linked standard Purchase transaction retains tax_id but tax_amount = 0.
8. Actual tax calculation/posting is deferred until the future actual purchase/receipt workflow.

DATABASE
--------
Existing tenant upgraded from the previous IS2289-2 parcel:
  Import Database/SQL/RiceMill_IS2289_2_Standard_Purchase_Flow_Tax_17Sep2026.sql

If the previous IS2289-2 SQL was NOT imported:
  Import Database/SQL/RiceMill_IS2289_2_Consolidated_17Sep2026.sql

Fresh installation:
  Use the updated Database/SQL/RiceMill_Master_SQL.sql
  OR run all Rice Mill migrations through 2026_09_17_000010.

DEPLOYMENT
----------
1. Replace the RiceMill module with this consolidated parcel.
2. Import the applicable tenant SQL into every tenant DB that uses Rice Mill.
3. Run: php artisan optimize:clear
4. Verify Rice Mill / Settings / Product Category Mapping has a valid Paddy Current Liabilities account mapping.
5. Test Purchase Order using Cash/Bank/Cheque and Credit Purchase (Due).
6. Confirm selected Purchase Tax is saved but does not alter the PO total/tax ledgers yet.
