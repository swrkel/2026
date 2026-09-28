StockTransferNew_STN_002

Continuation of standalone Stock Transfer-New module.

Added in this parcel:
1. Standalone stock movement ledger table: stnew_stock_movements.
2. Movement entity: StockTransferMovement.
3. Inventory service for dispatch and receive stock movement writing.
4. Short/excess quantity support in transfer lines.
5. Workflow hardening for Draft -> Pending Approval -> Approved -> In Transit -> Received.
6. Stock Movement report page and route.
7. POS-style reusable toolbar partial for list/report pages.
8. Improved transfer show page with action buttons, audit trail, requested/dispatched/received/short/excess columns.
9. Updated public CSS assets.
10. Separate SQL file for alter/create movement table.

SQL:
- Run 04_TENANT_ALTER_AND_MOVEMENT_TABLES_STOCK_TRANSFER_NEW.sql on each tenant database if STN_001 tables already exist.
- MASTER_STOCK_TRANSFER_NEW_SQL.sql is also updated with Stage 002 SQL.

PHP syntax check completed successfully for all module PHP files.
