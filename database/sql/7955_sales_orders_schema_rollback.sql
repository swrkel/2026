-- 7955 Sales Orders: rollback schema changes
-- MySQL 8+ compatible

START TRANSACTION;

-- 1) Drop newly created tables (reverse dependency order)
DROP TABLE IF EXISTS `distribution_sales_order_lines`;
DROP TABLE IF EXISTS `distribution_sales_orders`;
DROP TABLE IF EXISTS `distribution_route_user_maps`;

-- 2) Revert distribution_invoices added columns
ALTER TABLE `distribution_invoices`
DROP COLUMN `updated_by`,
DROP COLUMN `added_by`,
DROP COLUMN `sales_order_id`,
DROP COLUMN `status`,
DROP COLUMN `shipping_status`,
DROP COLUMN `shipping_details`,
DROP COLUMN `shipping_note`,
DROP COLUMN `invoice_note`,
DROP COLUMN `delivery_date`;

-- 3) Revert numbering_type enum in distribution_prefix_settings
ALTER TABLE `distribution_prefix_settings`
MODIFY COLUMN `numbering_type` ENUM('sales_invoice', 'daily_summary_sheet', 'loading_sheet') NOT NULL;

COMMIT;
