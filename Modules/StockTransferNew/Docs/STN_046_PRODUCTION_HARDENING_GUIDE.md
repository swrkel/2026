# StockTransferNew STN_046 - Production Hardening

## Purpose
This parcel adds final production-hardening utilities for StockTransfer-New before the complete consolidated production package.

## Added
- Production hardening dashboard
- Tenant/business/location/store scope checks
- Duplicate dispatch/receive checks
- Stock movement safety checks
- Permission readiness checks
- CSV export for tester/admin review
- SQL for new hardening tables and permissions

## Install
1. Copy the files into the existing Laravel codebase.
2. Include `Modules/StockTransferNew/Routes/stn_046_routes.php` from the module route loader if not auto-loaded.
3. Run `STN_046_CREATE_TABLES.sql` in each tenant database.
4. Run `STN_046_ALTER_TABLES.sql` in each tenant database after confirming the base STN tables exist.
5. Run `STN_046_INSERT_PERMISSIONS.sql` in each tenant database.
6. Clear route/config/view cache.

## Important
This package does not duplicate Products functionality. Product validation remains through the existing standalone Products module bridge.
