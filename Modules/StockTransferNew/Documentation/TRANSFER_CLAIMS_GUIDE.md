# StockTransfer-New STN_027 - Transfer Claims Guide

This package adds a standalone claim register for shortages, damages and excess variances found after transfer receipt.

## Use
1. Run `27_TENANT_TRANSFER_CLAIMS_STOCK_TRANSFER_NEW.sql` on every tenant database.
2. Register `Routes/admin_transfer_claims.php` in the module route loader if the module loader does not auto-load route files.
3. Publish/copy assets to `public/modules/stocktransfernew/`.
4. Give users the new claim permissions.

## Notes
- Product master data is not duplicated. Claim lines only store product/variation references and snapshot display fields from the transfer line.
- Finance posting is not duplicated. The module records claimed/recovered/write-off values so Finance can bridge if required.
- Closed claims cannot be cancelled.
