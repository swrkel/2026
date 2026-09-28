-- ============================================================================
-- PRODUCTS NEW - BUSINESS PRODUCT UID V1
-- STEP 03: CENTRAL / MASTER DATABASE REGISTRY TABLES
-- Date: 15 Sep 2026
--
-- Run ONLY in the CENTRAL / MASTER database.
--
-- The registry maps identities. It does NOT own tenant stock and it does NOT
-- merge equal names/SKUs/barcodes. There is deliberately NO location_id because
-- locations inside one business share the same Business Product UID.
-- ============================================================================

CREATE TABLE IF NOT EXISTS `products_new_uid_registry` (
    `product_uid` CHAR(36) NOT NULL,
    `tenant_key` VARCHAR(191) NOT NULL,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `tenant_product_id` BIGINT UNSIGNED NOT NULL,
    `source_database` VARCHAR(191) NULL,
    `product_name_snapshot` VARCHAR(191) NULL,
    `sku_snapshot` VARCHAR(191) NULL,
    `barcode_snapshot` VARCHAR(191) NULL,
    `source_schema_version` VARCHAR(64) NOT NULL DEFAULT 'business_product_uid_v1',
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `last_seen_at` DATETIME NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`product_uid`),
    UNIQUE KEY `pn_uid_registry_local_identity_unique` (`tenant_key`, `business_id`, `tenant_product_id`),
    KEY `pn_uid_registry_tenant_business_idx` (`tenant_key`, `business_id`),
    KEY `pn_uid_registry_source_database_idx` (`source_database`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `products_new_variation_uid_registry` (
    `variation_uid` CHAR(36) NOT NULL,
    `product_uid` CHAR(36) NOT NULL,
    `tenant_key` VARCHAR(191) NOT NULL,
    `business_id` BIGINT UNSIGNED NOT NULL,
    `tenant_product_id` BIGINT UNSIGNED NOT NULL,
    `tenant_variation_id` BIGINT UNSIGNED NOT NULL,
    `source_database` VARCHAR(191) NULL,
    `variation_sku_snapshot` VARCHAR(191) NULL,
    `source_schema_version` VARCHAR(64) NOT NULL DEFAULT 'business_product_uid_v1',
    `status` VARCHAR(32) NOT NULL DEFAULT 'active',
    `last_seen_at` DATETIME NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`variation_uid`),
    UNIQUE KEY `pn_var_uid_registry_local_identity_unique` (`tenant_key`, `business_id`, `tenant_variation_id`),
    KEY `pn_var_uid_registry_product_uid_idx` (`product_uid`),
    KEY `pn_var_uid_registry_tenant_business_idx` (`tenant_key`, `business_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `products_new_uid_tenant_capabilities` (
    `tenant_key` VARCHAR(191) NOT NULL,
    `source_database` VARCHAR(191) NOT NULL,
    `feature_version` VARCHAR(64) NOT NULL DEFAULT 'business_product_uid_v1',
    `product_uid_supported` TINYINT(1) NOT NULL DEFAULT 0,
    `variation_uid_supported` TINYINT(1) NOT NULL DEFAULT 0,
    `backfill_completed` TINYINT(1) NOT NULL DEFAULT 0,
    `last_seen_at` DATETIME NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`tenant_key`, `source_database`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Read-only verification.
SELECT
    (SELECT COUNT(*) FROM products_new_uid_registry) AS registered_products,
    (SELECT COUNT(*) FROM products_new_variation_uid_registry) AS registered_variations,
    (SELECT COUNT(*) FROM products_new_uid_tenant_capabilities) AS registered_tenant_capabilities;
