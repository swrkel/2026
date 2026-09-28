# STN_040 Production Consolidation Guide

This parcel adds the StockTransfer-New Production Console for final release validation.

## Included
- Production readiness checks
- Release sign-off register
- Duplicate reference check
- Open transfer review
- Variance review
- Lock review
- Separated tenant SQL

## Installation
1. Copy files into the Laravel project.
2. Include `Routes/stn_040_routes.php` from the module route loader if not auto-loaded.
3. Run the SQL files in each tenant database.
4. Clear Laravel route/view/config cache.
5. Open `/stock-transfer-new/production-console`.

## Notes
This package does not duplicate Products, Finance, SMS, POS, or Inventory master functionality.
