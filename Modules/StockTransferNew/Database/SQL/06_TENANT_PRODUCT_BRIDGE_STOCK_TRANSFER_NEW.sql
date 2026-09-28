-- StockTransferNew STN_004 - Product bridge notes
-- No new product tables are created in this stage.
-- StockTransferNew uses existing ProductsNew / ERP product master tables:
--   products, variations, variation_location_details
-- Optional movement bridge writes to products_new_inventory_movements only when that table exists.
-- This keeps StockTransferNew standalone without duplicating the standalone Products module.

-- Optional helpful index if not already available in tenant DB:
-- CREATE INDEX stnew_stock_movements_product_ref_idx ON stnew_stock_movements(product_id, variation_id, reference_no);
