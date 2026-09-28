StockTransferNew STN_003

Added in this parcel:
1. Standalone stock balance mirror table: stnew_stock_balances.
2. Dispatch now reduces source on-hand qty and increases source in-transit qty inside StockTransferNew.
3. Receive now increases destination on-hand qty and reduces source in-transit qty.
4. Full edit/update draft workflow with line replacement for draft/rejected transfers.
5. Workflow guards to prevent wrong status changes.
6. Dispatch Note and Receive Note printable documents.
7. Vehicle/driver fields for transfer transport tracking.
8. Batch No and Expiry Date fields on transfer lines.
9. Balances page and balance report.
10. Separate tenant SQL file: 05_TENANT_ALTER_BALANCES_DOCUMENTS_STOCK_TRANSFER_NEW.sql.

Install order:
- Upload this full module replacement over Modules/StockTransferNew.
- Run migrations OR run SQL files in the numbered order per tenant database.
- Master DB SQL remains in earlier SQL files unless this module is not yet enabled in the master modules list.
