StockTransferNew_STN_004

Purpose:
- Continue Stock Transfer-New after STN_003.
- Respect existing standalone Products module; no duplicate product master created.

Added/changed:
- StockTransferProductBridgeService for product lookup, variations, available stock, standard stock updates.
- API endpoints for product search, variations, and available-stock checks.
- Optional ProductsNew movement bridge when products_new_inventory_movements exists.
- Dispatch/receive now also adjusts standard variation_location_details when variation/location data is available.
- Create screen product-master note added.
- StoreStockTransferRequest validation added for cleaner future controller hardening.
- SQL note file 06_TENANT_PRODUCT_BRIDGE_STOCK_TRANSFER_NEW.sql added.

Important:
- Products remain owned by ProductsNew / ERP product master.
- StockTransferNew is standalone but integrates through safe table/service bridges only.
