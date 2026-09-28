# STN_043 Predictive Transfer Planning

This parcel adds controlled predictive transfer planning and workload balancing.

## Important
- Product master data is not duplicated.
- Product and variation IDs are referenced from the existing standalone Products module.
- Generated recommendations remain in review until an authorized user approves or rejects them.
- This package does not automatically create final transfer records without review.

## Installation
1. Copy the changed files into the Laravel project.
2. Run the tenant SQL files on each tenant database that will use StockTransfer-New.
3. Add `stn_043_routes.php` to the StockTransferNew module route loader if your loader does not auto-load module route files.
4. Clear Laravel cache.
