# Products New Stage 004 - Stock & Price Center

This parcel extends Products New beyond the master file into operational inventory and pricing pages.

## Added UI pages
- Products New > Stock Center enhanced with low / zero / negative stock status.
- Products New > Inventory Movements.
- Products New > Opening Stock.
- Products New > Price Center.

## Added standalone files
- InventoryMovementController
- OpeningStockController
- PriceCenterController
- InventoryMovementService
- OpeningStockService
- PriceCenterService
- ProductsNewInventoryMovement entity
- ProductsNewOpeningStockSession entity
- ProductsNewPriceTier entity
- ProductsNewBarcodeQueue entity
- Separate Blade files for each page

## SQL
Run `ProductsNew_STAGE004_PRODUCTSNEW_004_STOCK_PRICE_CENTER.sql` inside each tenant database.
The master SQL file has also been updated.

## Notes
Existing Product module is not changed. The new pages operate through Products New routes only under `/products-new`.
