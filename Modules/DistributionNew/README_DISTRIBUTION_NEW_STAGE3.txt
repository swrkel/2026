Distribution New - Stage 3 (DISNEW_003)

Upload/replace the DistributionNew module folder.
Run only Database/SQL/DISNEW_003_LOADING_UNLOADING_VEHICLE_STOCK.sql for this package.
For a fresh tenant database, run Database/SQL/DISNEW_MASTER.sql.

Main URLs:
- /distribution-new/loading-plans
- /distribution-new/loading
- /distribution-new/unloading
- /distribution-new/vehicle-store-stock

Notes:
- All new tables use disnew_ prefix.
- SMS is still kept as a bridge/event layer to the existing SMS module.
- Customer records are not duplicated; sales order customer references remain compatible with the existing Customers module.
