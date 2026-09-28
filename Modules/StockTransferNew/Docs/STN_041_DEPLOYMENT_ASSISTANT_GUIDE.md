# STN 041 - Deployment Assistant Guide

This parcel adds server deployment support screens for StockTransfer-New.

## Pages
- `/stock-transfer-new/deployment` - server checks
- `/stock-transfer-new/deployment/sql-tracker` - SQL execution tracker
- `/stock-transfer-new/deployment/rollback` - rollback readiness plan

## Usage
1. Upload files from this package.
2. Run the SQL in the tenant database.
3. Open the deployment page and check missing tables, permissions and assets.
4. Mark SQL scripts as executed after applying them.

This package does not duplicate Products, Finance or Communication Hub modules.
