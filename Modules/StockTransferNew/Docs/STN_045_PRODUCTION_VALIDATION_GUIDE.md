# STN_045 Production Validation

This parcel adds a safe production validation layer for StockTransfer-New.

## Purpose
- Check core module tables before go-live.
- Detect duplicate transfer references.
- Detect stale in-transit transfers.
- Detect unresolved dispatch/receive variances.
- Detect negative stock movement balances.
- Verify StockTransferNew permissions are seeded.
- Verify that the standalone Products module source table is available for the product bridge.

## Installation
1. Copy the changed files into the application.
2. Include `Modules/StockTransferNew/Routes/stn_045_routes.php` from the module route service provider if not auto-loaded.
3. Run the tenant SQL files on each tenant database.
4. Run the permission insert SQL once per database where permissions are stored.
5. Open `/stock-transfer-new/production-validation`.

## Notes
- This parcel does not duplicate product functionality.
- It only checks availability of a product source table from the existing Products module.
- It is a validation/support layer and does not alter the completed transfer workflow.
