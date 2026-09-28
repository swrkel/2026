# Tea Estate Management -> Finance integration

Tea Estate Management owns its operational records in `tea_*` tables. It does not import Finance PHP models/controllers/services. Financial integration is performed by the module's own `TeaFinanceBridgeService` against the common accounting ledger tables when they exist.

## Posting rules

1. **Plantation / field activity cost**  
   Dr Tea Estate Operating Expense / Cr selected Finance Cash or Bank account.
2. **Own-estate harvest valued at standard cost**  
   Dr Green Leaf Inventory / Cr Estate Production Cost Recovery.
3. **Green-leaf purchase**  
   Dr Green Leaf Inventory / Cr Tea Supplier Payable.
4. **Supplier payment**  
   Dr Tea Supplier Payable / Cr selected Finance Cash or Bank account.
5. **Processing-stage direct cost**  
   Dr Tea Processing WIP / Cr selected Finance Cash or Bank account.
6. **Processing finalisation**  
   Dr Finished Tea Inventory for total batch cost / Cr Green Leaf Inventory for leaf input cost / Cr Tea Processing WIP for processing-stage cost.
7. **Tea sale**  
   Dr Tea Accounts Receivable / Cr Tea Sales Revenue / Cr Tax Payable where tax exists.
8. **Cost of tea sold**  
   Dr Cost of Tea Sold / Cr Finished Tea Inventory using the issued inventory-lot cost.
9. **Customer receipt**  
   Dr selected Finance Cash or Bank account / Cr Tea Accounts Receivable.

## Finance visibility

Every posted Tea finance source gets a standard `transactions` row with `business_id`, `location_id`, document number and operation date. Balanced `account_transactions` rows point to that transaction and to the mapped Finance accounts. Consequently the Tea entries are available to the existing Finance Account Books and reports that use the standard accounting ledger, including Trial Balance, Profit & Loss/Income Statement, Balance Sheet and cash/bank account activity according to the account mappings.

## Reliability and duplicate protection

`tea_finance_events` is an idempotent outbox/reconciliation table. Its unique business/source/event key prevents duplicate ledger posting on retries. If Finance tables are unavailable, an account mapping is missing, or a posting fails, the Tea operational transaction remains recorded and the finance event stays pending/error for controlled retry from **Tea Estate Management -> Finance Integration**.

The Tea module never silently guesses a missing accounting account. Configure the default or location-specific mappings before live use. Location-specific mappings override the default (`location_id = 0`) mapping.
