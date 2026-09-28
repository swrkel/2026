STOCK_TRANSFER_NEW_011_MANIFEST
Package: StockTransferNew_STN_011
Scope: Warehouse/mobile scan operations layer.

This ZIP contains only new/changed files for the standalone StockTransferNew module.

Added:
- Warehouse mobile dashboard
- Fast dispatch scan screen
- Fast receive scan screen
- QR code transfer lookup workflow
- Scan session and scan line records
- Warehouse scan service with duplicate scan protection
- Variance preview before receive confirmation
- Mobile/PDA friendly CSS and JS
- Tenant SQL separated for create/alter/insert

Notes:
- Does not create duplicate product master data.
- Product information remains bridged from the existing standalone Products module.
- Designed for tenant databases in the multi-tenant application.
