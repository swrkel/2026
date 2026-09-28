# Distribution Optimized Raw SQL (MySQL 8+)

This script is tailored to the current project table names:

- `distribution_sales_orders`
- `distribution_sales_order_lines`
- `distribution_route_user_maps`
- `business`
- `routes`
- `users`

It is designed to run in one pass with minimal risk on existing environments:

- Uses `CREATE TABLE IF NOT EXISTS`.
- Uses `ADD COLUMN IF NOT EXISTS` and `ADD INDEX IF NOT EXISTS`.
- Avoids strict foreign keys to legacy core tables where column-type mismatch may break deployment (`BIGINT` in new distribution tables vs `INT` in some legacy parent tables).

If you want strict FK enforcement later, do it in a separate controlled migration after data type alignment.

## 1) UP Script (Run All At Once)

```sql
/* =========================================================
   A. DISTRIBUTION SALES ORDER LINES
   ========================================================= */
CREATE TABLE IF NOT EXISTS `distribution_sales_order_lines` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sales_order_id` BIGINT UNSIGNED NOT NULL,
  `product_id` BIGINT UNSIGNED NOT NULL,
  `variation_id` BIGINT UNSIGNED NULL,
  `unit_id` BIGINT UNSIGNED NULL,
  `qty` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `discount_type` VARCHAR(20) NOT NULL DEFAULT 'fixed',
  `final_amount` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0,
  `is_free` TINYINT(1) NOT NULL DEFAULT 0,
  `is_free_bottles` TINYINT(1) NOT NULL DEFAULT 0,
  `is_free_auto` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `distribution_sales_order_lines_sales_order_id_index` (`sales_order_id`),
  KEY `distribution_sales_order_lines_product_id_index` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* Add missing columns/indexes safely for existing table */
ALTER TABLE `distribution_sales_order_lines`
  ADD COLUMN IF NOT EXISTS `variation_id` BIGINT UNSIGNED NULL AFTER `product_id`,
  ADD COLUMN IF NOT EXISTS `unit_id` BIGINT UNSIGNED NULL AFTER `variation_id`,
  ADD COLUMN IF NOT EXISTS `qty` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `unit_id`,
  ADD COLUMN IF NOT EXISTS `quantity` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `qty`,
  ADD COLUMN IF NOT EXISTS `unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `quantity`,
  ADD COLUMN IF NOT EXISTS `amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `unit_price`,
  ADD COLUMN IF NOT EXISTS `discount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `amount`,
  ADD COLUMN IF NOT EXISTS `discount_type` VARCHAR(20) NOT NULL DEFAULT 'fixed' AFTER `discount`,
  ADD COLUMN IF NOT EXISTS `final_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `discount_type`,
  ADD COLUMN IF NOT EXISTS `line_total` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `final_amount`,
  ADD COLUMN IF NOT EXISTS `is_free` TINYINT(1) NOT NULL DEFAULT 0 AFTER `line_total`,
  ADD COLUMN IF NOT EXISTS `is_free_bottles` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_free`,
  ADD COLUMN IF NOT EXISTS `is_free_auto` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_free_bottles`,
  ADD INDEX IF NOT EXISTS `distribution_sales_order_lines_sales_order_id_index` (`sales_order_id`),
  ADD INDEX IF NOT EXISTS `distribution_sales_order_lines_product_id_index` (`product_id`),
  ADD INDEX IF NOT EXISTS `distribution_sales_order_lines_variation_id_index` (`variation_id`),
  ADD INDEX IF NOT EXISTS `idx_dsol_sales_order_product` (`sales_order_id`, `product_id`),
  ADD INDEX IF NOT EXISTS `idx_dsol_sales_order_created` (`sales_order_id`, `created_at`);


/* =========================================================
   B. DISTRIBUTION ROUTE USER MAPS
   ========================================================= */
CREATE TABLE IF NOT EXISTS `distribution_route_user_maps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `route_id` BIGINT UNSIGNED NOT NULL,
  `sales_rep_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `last_status_from` VARCHAR(20) NULL,
  `last_status_to` VARCHAR(20) NULL,
  `status_changed_at` TIMESTAMP NULL,
  `added_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `distribution_route_user_map_unique` (`business_id`, `route_id`, `sales_rep_id`),
  KEY `distribution_route_user_maps_business_id_index` (`business_id`),
  KEY `distribution_route_user_maps_route_id_index` (`route_id`),
  KEY `distribution_route_user_maps_sales_rep_id_index` (`sales_rep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* Add missing status audit + performance indexes safely */
ALTER TABLE `distribution_route_user_maps`
  ADD COLUMN IF NOT EXISTS `last_status_from` VARCHAR(20) NULL AFTER `status`,
  ADD COLUMN IF NOT EXISTS `last_status_to` VARCHAR(20) NULL AFTER `last_status_from`,
  ADD COLUMN IF NOT EXISTS `status_changed_at` TIMESTAMP NULL AFTER `last_status_to`,
  ADD INDEX IF NOT EXISTS `distribution_route_user_maps_business_id_index` (`business_id`),
  ADD INDEX IF NOT EXISTS `distribution_route_user_maps_route_id_index` (`route_id`),
  ADD INDEX IF NOT EXISTS `distribution_route_user_maps_sales_rep_id_index` (`sales_rep_id`),
  ADD UNIQUE INDEX IF NOT EXISTS `distribution_route_user_map_unique` (`business_id`, `route_id`, `sales_rep_id`),
  ADD INDEX IF NOT EXISTS `idx_route_user_maps_status_changed_at` (`status_changed_at`),
  ADD INDEX IF NOT EXISTS `idx_route_user_maps_status_time` (`status`, `status_changed_at`),
  ADD INDEX IF NOT EXISTS `idx_route_user_maps_status_route` (`status`, `status_changed_at`, `route_id`),
  ADD INDEX IF NOT EXISTS `idx_route_user_maps_status_sales_rep` (`status`, `status_changed_at`, `sales_rep_id`);
```

## 2) DOWN Script (Rollback)

```sql
/* Roll back in dependency-safe order */
DROP TABLE IF EXISTS `distribution_route_user_maps`;
DROP TABLE IF EXISTS `distribution_sales_order_lines`;
```

## 3) Why This Is Faster

- Composite indexes added for expected filter patterns:
  - `distribution_sales_order_lines (sales_order_id, product_id)`
  - `distribution_route_user_maps (status, status_changed_at, route_id)`
  - `distribution_route_user_maps (status, status_changed_at, sales_rep_id)`
- Unique map key prevents duplicate route-user assignment rows.
- Status-time indexes speed up operational dashboards and status timeline lookups.

## 4) Important Note on Foreign Keys

The script intentionally avoids strict FKs to `business`, `routes`, `users`, and `products` because legacy parent IDs are typically `INT UNSIGNED` while current distribution tables use `BIGINT UNSIGNED`.  
Adding FK constraints without first aligning data types can fail in production.

