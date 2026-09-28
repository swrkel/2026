ALTER TABLE `restaurant_new_stock_movements`
  ADD COLUMN `source_module` VARCHAR(80) NULL,
  ADD COLUMN `source_reference` VARCHAR(80) NULL;
ALTER TABLE `restaurant_new_ingredients`
  ADD COLUMN `default_supplier_name` VARCHAR(255) NULL AFTER `name`,
  ADD COLUMN `last_purchase_cost` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `default_supplier_name`;
