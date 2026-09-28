Stock Transfer-New Stage 014 Final Readiness

This stage completes the development parcels up to production-readiness preparation.

Deployment order:
1. Upload all StockTransferNew module files from the latest full package or all incremental parcels in sequence.
2. Upload public assets under public/modules/stocktransfernew.
3. Run tenant SQL files in this order:
   a. 01_MASTER_CREATE_TABLES_STOCK_TRANSFER_NEW.sql
   b. 02_MASTER_ALTER_TABLES_STOCK_TRANSFER_NEW.sql
   c. 03_MASTER_INSERT_PERMISSIONS_STOCK_TRANSFER_NEW.sql
   d. 04_MASTER_INDEXES_STOCK_TRANSFER_NEW.sql
4. Clear Laravel caches if your deployment process requires it.
5. Enable module in modules_statuses.json if not already enabled.
6. Confirm permissions are assigned to the required roles.

The module remains standalone with its own controllers, services, models/entities, views, assets, routes, language files, SQL and reports.
