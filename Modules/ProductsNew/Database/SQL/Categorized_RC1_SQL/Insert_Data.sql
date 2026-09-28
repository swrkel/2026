-- Statement 32
INSERT IGNORE INTO products_new_product_statuses (business_id, code, name, color, is_default, is_active, sort_order, allowed_next_statuses, created_at, updated_at) VALUES
(NULL, 'draft', 'Draft', '#6c757d', 0, 1, 10, JSON_ARRAY('pending_review','active','archived'), NOW(), NOW()),
(NULL, 'pending_review', 'Pending Review', '#ffc107', 0, 1, 20, JSON_ARRAY('active','draft','suspended'), NOW(), NOW()),
(NULL, 'active', 'Active', '#28a745', 1, 1, 30, JSON_ARRAY('suspended','discontinued','archived'), NOW(), NOW()),
(NULL, 'suspended', 'Suspended', '#fd7e14', 0, 1, 40, JSON_ARRAY('active','discontinued','archived'), NOW(), NOW()),
(NULL, 'discontinued', 'Discontinued', '#dc3545', 0, 1, 50, JSON_ARRAY('active','archived'), NOW(), NOW()),
(NULL, 'archived', 'Archived', '#343a40', 0, 1, 60, JSON_ARRAY('active'), NOW(), NOW());

-- Statement 56
INSERT IGNORE INTO products_new_import_validation_rules (business_id, rule_code, rule_name, is_required, severity, created_at, updated_at) VALUES
(NULL, 'product_name_required', 'Product name is required', 1, 'error', NOW(), NOW()),
(NULL, 'sku_required', 'SKU is required', 1, 'error', NOW(), NOW()),
(NULL, 'selling_price_numeric', 'Selling price must be numeric', 1, 'error', NOW(), NOW()),
(NULL, 'purchase_price_numeric', 'Purchase price must be numeric', 1, 'error', NOW(), NOW());

-- Statement 99
INSERT INTO `products_new_product_types` (`business_id`,`name`,`code`,`description`,`is_active`,`created_at`,`updated_at`)
SELECT b.id, x.name, x.code, x.description, 1, NOW(), NOW()
FROM businesses b
JOIN (
  SELECT 'Physical Product' name, 'physical_product' code, 'Standard stock item' description UNION ALL
  SELECT 'Service', 'service', 'Non-stock service item' UNION ALL
  SELECT 'Raw Material', 'raw_material', 'Manufacturing input' UNION ALL
  SELECT 'Finished Goods', 'finished_goods', 'Manufactured sale item' UNION ALL
  SELECT 'Spare Parts', 'spare_parts', 'Service and vehicle spare part' UNION ALL
  SELECT 'Fuel Product', 'fuel_product', 'Fuel or petroleum product' UNION ALL
  SELECT 'Hotel Item', 'hotel_item', 'Hotel, kitchen, housekeeping or room item' UNION ALL
  SELECT 'Medical Item', 'medical_item', 'Medical/pharmacy product with batch and expiry controls' UNION ALL
  SELECT 'Rental Item', 'rental_item', 'Asset or item available for rental' UNION ALL
  SELECT 'Bundle / Package', 'bundle_package', 'Kit, combo, package or bundle'
) x
WHERE NOT EXISTS (SELECT 1 FROM products_new_product_types t WHERE t.business_id=b.id AND t.code=x.code);
