# STN_042 - Operations Control Center

Adds final enterprise operations screens for StockTransfer-New:
- Live transfer status board
- SLA breach monitoring
- Manual escalation register
- Warehouse workload view
- Cross-location transfer calendar
- CSV export

This package remains standalone and does not duplicate Products, Finance, Inventory, POS, Communication Hub, or SMS logic.

## SQL order
1. STN_042_CREATE_TABLES.sql
2. STN_042_ALTER_TABLES.sql
3. STN_042_INSERT_PERMISSIONS.sql

## Routes
Load `Modules/StockTransferNew/Routes/stn_042_routes.php` from the module route provider or merge it into the module route loader.
