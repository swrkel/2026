-- ================================================================
-- Simple Audit (SAU) - Tenant Database Installation
-- Target: MariaDB 10.6+ / Laravel tenant database
-- All module-owned TABLES use the required sau_ prefix.
-- Generated for nivasa_template schema dated 20 Sep 2026.
-- ================================================================

-- ----------------------------------------------------------------
-- phpMyAdmin / manual-import safety check.
-- This file MUST be imported while a TENANT database is selected.
-- It deliberately stops before creating anything if a system database or
-- a database without the ERP tenant tables is selected.
-- ----------------------------------------------------------------
SET @sau_target_db := DATABASE();
SET @sau_guard_sql := IF(
    @sau_target_db IS NULL OR @sau_target_db IN ('information_schema','mysql','performance_schema','sys'),
    "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Simple Audit: select the TENANT database first; do not import into information_schema/mysql/performance_schema/sys'",
    "SELECT CONCAT('Simple Audit tenant install target: ', DATABASE()) AS status"
);
PREPARE sau_guard_stmt FROM @sau_guard_sql;
EXECUTE sau_guard_stmt;
DEALLOCATE PREPARE sau_guard_stmt;

SET @sau_required_table_count := (
    SELECT COUNT(DISTINCT table_name)
      FROM information_schema.tables
     WHERE table_schema = DATABASE()
       AND table_name IN ('business','transactions','products','variation_location_details')
);
SET @sau_guard_sql := IF(
    @sau_required_table_count < 4,
    "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Simple Audit: selected database is not a tenant ERP database (required ERP tables are missing)'",
    "SELECT 'Simple Audit tenant database preflight: OK' AS status"
);
PREPARE sau_guard_stmt FROM @sau_guard_sql;
EXECUTE sau_guard_stmt;
DEALLOCATE PREPARE sau_guard_stmt;
SET @sau_guard_sql := NULL;
SET @sau_required_table_count := NULL;

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

CREATE TABLE IF NOT EXISTS `sau_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `key_name` varchar(120) NOT NULL,
  `value_json` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sau_settings_business_key_uq` (`business_id`,`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sau_change_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint(20) unsigned DEFAULT NULL,
  `location_id` bigint(20) unsigned DEFAULT NULL,
  `store_id` bigint(20) unsigned DEFAULT NULL,
  `source_table` varchar(80) NOT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `event_type` varchar(20) NOT NULL,
  `transaction_id` bigint(20) unsigned DEFAULT NULL,
  `payment_id` bigint(20) unsigned DEFAULT NULL,
  `contact_id` bigint(20) unsigned DEFAULT NULL,
  `account_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` bigint(20) unsigned DEFAULT NULL,
  `variation_id` bigint(20) unsigned DEFAULT NULL,
  `actor_user_id` bigint(20) unsigned DEFAULT NULL,
  `old_data` longtext DEFAULT NULL,
  `new_data` longtext DEFAULT NULL,
  `occurred_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sau_change_events_business_id_index` (`business_id`),
  KEY `sau_change_events_location_id_index` (`location_id`),
  KEY `sau_change_events_store_id_index` (`store_id`),
  KEY `sau_change_events_source_table_index` (`source_table`),
  KEY `sau_change_events_source_id_index` (`source_id`),
  KEY `sau_change_events_event_type_index` (`event_type`),
  KEY `sau_change_events_transaction_id_index` (`transaction_id`),
  KEY `sau_change_events_payment_id_index` (`payment_id`),
  KEY `sau_change_events_contact_id_index` (`contact_id`),
  KEY `sau_change_events_account_id_index` (`account_id`),
  KEY `sau_change_events_product_id_index` (`product_id`),
  KEY `sau_change_events_variation_id_index` (`variation_id`),
  KEY `sau_change_events_actor_user_id_index` (`actor_user_id`),
  KEY `sau_change_events_occurred_at_index` (`occurred_at`),
  KEY `sau_events_business_time_idx` (`business_id`,`occurred_at`),
  KEY `sau_events_location_time_idx` (`business_id`,`location_id`,`occurred_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sau_stock_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint(20) unsigned DEFAULT NULL,
  `location_id` bigint(20) unsigned NOT NULL,
  `product_id` bigint(20) unsigned NOT NULL,
  `variation_id` bigint(20) unsigned NOT NULL,
  `qty_before` decimal(22,6) DEFAULT NULL,
  `qty_after` decimal(22,6) DEFAULT NULL,
  `source` varchar(50) NOT NULL DEFAULT 'variation_location_details',
  `source_row_id` bigint(20) unsigned DEFAULT NULL,
  `changed_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sau_stock_snapshots_business_id_index` (`business_id`),
  KEY `sau_stock_snapshots_location_id_index` (`location_id`),
  KEY `sau_stock_snapshots_product_id_index` (`product_id`),
  KEY `sau_stock_snapshots_variation_id_index` (`variation_id`),
  KEY `sau_stock_snapshots_changed_at_index` (`changed_at`),
  KEY `sau_stock_lookup_idx` (`business_id`,`location_id`,`product_id`,`variation_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sau_report_shares` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(96) NOT NULL,
  `tenant_id` varchar(191) DEFAULT NULL,
  `business_id` bigint(20) unsigned NOT NULL,
  `location_id` bigint(20) unsigned DEFAULT NULL,
  `store_id` bigint(20) unsigned DEFAULT NULL,
  `date_from` date NOT NULL,
  `date_to` date NOT NULL,
  `filters_json` longtext DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sau_report_shares_token_unique` (`token`),
  KEY `sau_report_shares_tenant_id_index` (`tenant_id`),
  KEY `sau_report_shares_business_id_index` (`business_id`),
  KEY `sau_report_shares_location_id_index` (`location_id`),
  KEY `sau_report_shares_store_id_index` (`store_id`),
  KEY `sau_report_shares_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sau_activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `reference_type` varchar(80) DEFAULT NULL,
  `reference_id` varchar(191) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `meta_json` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sau_activity_logs_business_id_index` (`business_id`),
  KEY `sau_activity_logs_user_id_index` (`user_id`),
  KEY `sau_activity_logs_action_index` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial stock baseline. It is inserted only once.
-- Store the target-table check in a variable first. This avoids MySQL/MariaDB
-- target-table self-reference edge cases during INSERT ... SELECT imports.
SET @sau_has_stock_baseline := (SELECT COUNT(*) FROM `sau_stock_snapshots` WHERE `source`='baseline');
INSERT INTO `sau_stock_snapshots`
(`business_id`,`location_id`,`product_id`,`variation_id`,`qty_before`,`qty_after`,`source`,`source_row_id`,`changed_at`,`created_at`)
SELECT p.business_id, vld.location_id, vld.product_id, vld.variation_id,
       COALESCE(vld.qty_available,0), COALESCE(vld.qty_available,0),
       'baseline', vld.id, NOW(), NOW()
FROM variation_location_details vld
JOIN products p ON p.id=vld.product_id
WHERE @sau_has_stock_baseline = 0;
SET @sau_has_stock_baseline := NULL;

INSERT INTO `sau_settings` (`business_id`,`key_name`,`value_json`,`created_at`,`updated_at`)
VALUES (0,'stock_tracking_started_at',JSON_OBJECT('started_at',DATE_FORMAT(NOW(),'%Y-%m-%d %H:%i:%s')),NOW(),NOW())
ON DUPLICATE KEY UPDATE `updated_at`=VALUES(`updated_at`);

INSERT INTO `sau_settings` (`business_id`,`key_name`,`value_json`,`created_at`,`updated_at`)
VALUES (0,'schema_version',JSON_OBJECT('version',1),NOW(),NOW())
ON DUPLICATE KEY UPDATE `value_json`=VALUES(`value_json`),`updated_at`=VALUES(`updated_at`);

INSERT INTO `sau_settings` (`business_id`,`key_name`,`value_json`,`created_at`,`updated_at`)
VALUES (0,'audit_tracking_started_at',JSON_OBJECT('started_at',DATE_FORMAT(NOW(),'%Y-%m-%d %H:%i:%s')),NOW(),NOW())
ON DUPLICATE KEY UPDATE `key_name`=VALUES(`key_name`);

-- ----------------------------------------------------------------
-- Module-owned capture triggers. They do not modify source rows.
-- ----------------------------------------------------------------
DELIMITER $$

DROP TRIGGER IF EXISTS `sau_vld_ai`$$
CREATE TRIGGER `sau_vld_ai` AFTER INSERT ON `variation_location_details`
FOR EACH ROW
BEGIN
    INSERT INTO sau_stock_snapshots
        (business_id, location_id, product_id, variation_id, qty_before, qty_after, source, source_row_id, changed_at, created_at)
    SELECT p.business_id, NEW.location_id, NEW.product_id, NEW.variation_id,
           NULL, COALESCE(NEW.qty_available,0), 'variation_location_details', NEW.id, NOW(), NOW()
      FROM products p WHERE p.id = NEW.product_id LIMIT 1;
END$$

DROP TRIGGER IF EXISTS `sau_vld_au`$$
CREATE TRIGGER `sau_vld_au` AFTER UPDATE ON `variation_location_details`
FOR EACH ROW
BEGIN
    IF NOT (OLD.qty_available <=> NEW.qty_available) THEN
        INSERT INTO sau_stock_snapshots
            (business_id, location_id, product_id, variation_id, qty_before, qty_after, source, source_row_id, changed_at, created_at)
        SELECT p.business_id, NEW.location_id, NEW.product_id, NEW.variation_id,
               COALESCE(OLD.qty_available,0), COALESCE(NEW.qty_available,0), 'variation_location_details', NEW.id, NOW(), NOW()
          FROM products p WHERE p.id = NEW.product_id LIMIT 1;
    END IF;
END$$

DROP TRIGGER IF EXISTS `sau_vld_ad`$$
CREATE TRIGGER `sau_vld_ad` AFTER DELETE ON `variation_location_details`
FOR EACH ROW
BEGIN
    INSERT INTO sau_stock_snapshots
        (business_id, location_id, product_id, variation_id, qty_before, qty_after, source, source_row_id, changed_at, created_at)
    SELECT p.business_id, OLD.location_id, OLD.product_id, OLD.variation_id,
           COALESCE(OLD.qty_available,0), 0, 'variation_location_details_delete', OLD.id, NOW(), NOW()
      FROM products p WHERE p.id = OLD.product_id LIMIT 1;
END$$

DROP TRIGGER IF EXISTS `sau_transactions_ai`$$
CREATE TRIGGER `sau_transactions_ai` AFTER INSERT ON `transactions`
FOR EACH ROW
BEGIN
    IF NEW.type IN ('purchase','purchase_return','stock_adjustment') THEN
        INSERT INTO sau_change_events
        (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, contact_id, actor_user_id, old_data, new_data, occurred_at, created_at)
        VALUES
        (NEW.business_id, NEW.location_id, NEW.store_id, 'transactions', NEW.id, 'insert', NEW.id, NEW.contact_id, NEW.created_by, NULL,
         JSON_OBJECT('type',NEW.type,'status',NEW.status,'transaction_date',NEW.transaction_date,'contact_id',NEW.contact_id,'ref_no',NEW.ref_no,'purchase_entry_no',NEW.purchase_entry_no,'total_before_tax',NEW.total_before_tax,'tax_amount',NEW.tax_amount,'discount_amount',NEW.discount_amount,'final_total',NEW.final_total,'payment_status',NEW.payment_status,'deleted_at',NEW.deleted_at),
         NOW(), NOW());
    END IF;
END$$

DROP TRIGGER IF EXISTS `sau_transactions_au`$$
CREATE TRIGGER `sau_transactions_au` AFTER UPDATE ON `transactions`
FOR EACH ROW
BEGIN
    IF (NEW.type IN ('purchase','purchase_return','stock_adjustment') OR OLD.type IN ('purchase','purchase_return','stock_adjustment'))
       AND (
           NOT (OLD.status <=> NEW.status) OR NOT (OLD.transaction_date <=> NEW.transaction_date) OR
           NOT (OLD.contact_id <=> NEW.contact_id) OR NOT (OLD.location_id <=> NEW.location_id) OR
           NOT (OLD.store_id <=> NEW.store_id) OR NOT (OLD.total_before_tax <=> NEW.total_before_tax) OR
           NOT (OLD.tax_amount <=> NEW.tax_amount) OR NOT (OLD.discount_amount <=> NEW.discount_amount) OR
           NOT (OLD.final_total <=> NEW.final_total) OR NOT (OLD.payment_status <=> NEW.payment_status) OR
           NOT (OLD.deleted_at <=> NEW.deleted_at) OR NOT (OLD.ref_no <=> NEW.ref_no) OR
           NOT (OLD.purchase_entry_no <=> NEW.purchase_entry_no)
       ) THEN
        INSERT INTO sau_change_events
        (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, contact_id, actor_user_id, old_data, new_data, occurred_at, created_at)
        VALUES
        (NEW.business_id, NEW.location_id, NEW.store_id, 'transactions', NEW.id, 'update', NEW.id, NEW.contact_id, NEW.created_by,
         JSON_OBJECT('type',OLD.type,'status',OLD.status,'transaction_date',OLD.transaction_date,'contact_id',OLD.contact_id,'ref_no',OLD.ref_no,'purchase_entry_no',OLD.purchase_entry_no,'total_before_tax',OLD.total_before_tax,'tax_amount',OLD.tax_amount,'discount_amount',OLD.discount_amount,'final_total',OLD.final_total,'payment_status',OLD.payment_status,'deleted_at',OLD.deleted_at),
         JSON_OBJECT('type',NEW.type,'status',NEW.status,'transaction_date',NEW.transaction_date,'contact_id',NEW.contact_id,'ref_no',NEW.ref_no,'purchase_entry_no',NEW.purchase_entry_no,'total_before_tax',NEW.total_before_tax,'tax_amount',NEW.tax_amount,'discount_amount',NEW.discount_amount,'final_total',NEW.final_total,'payment_status',NEW.payment_status,'deleted_at',NEW.deleted_at),
         NOW(), NOW());
    END IF;
END$$

DROP TRIGGER IF EXISTS `sau_transactions_ad`$$
CREATE TRIGGER `sau_transactions_ad` AFTER DELETE ON `transactions`
FOR EACH ROW
BEGIN
    IF OLD.type IN ('purchase','purchase_return','stock_adjustment') THEN
        INSERT INTO sau_change_events
        (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, contact_id, actor_user_id, old_data, new_data, occurred_at, created_at)
        VALUES
        (OLD.business_id, OLD.location_id, OLD.store_id, 'transactions', OLD.id, 'delete', OLD.id, OLD.contact_id, OLD.created_by,
         JSON_OBJECT('type',OLD.type,'status',OLD.status,'transaction_date',OLD.transaction_date,'contact_id',OLD.contact_id,'ref_no',OLD.ref_no,'purchase_entry_no',OLD.purchase_entry_no,'total_before_tax',OLD.total_before_tax,'tax_amount',OLD.tax_amount,'discount_amount',OLD.discount_amount,'final_total',OLD.final_total,'payment_status',OLD.payment_status,'deleted_at',OLD.deleted_at),
         NULL, NOW(), NOW());
    END IF;
END$$

DROP TRIGGER IF EXISTS `sau_purchase_lines_ai`$$
CREATE TRIGGER `sau_purchase_lines_ai` AFTER INSERT ON `purchase_lines`
FOR EACH ROW
BEGIN
    INSERT INTO sau_change_events
    (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, contact_id, product_id, variation_id, actor_user_id, old_data, new_data, occurred_at, created_at)
    SELECT t.business_id, t.location_id, t.store_id, 'purchase_lines', NEW.id, 'insert', t.id, t.contact_id, NEW.product_id, NEW.variation_id, t.created_by,
           NULL,
           JSON_OBJECT('quantity',NEW.quantity,'purchase_price',NEW.purchase_price,'purchase_price_inc_tax',NEW.purchase_price_inc_tax,'discount_amount',NEW.discount_amount,'discount_percent',NEW.discount_percent,'item_tax',NEW.item_tax,'tax_id',NEW.tax_id,'quantity_returned',NEW.quantity_returned,'deleted_at',NEW.deleted_at,'new_deleted_at',NEW.new_deleted_at),
           NOW(), NOW()
      FROM transactions t
     WHERE t.id = NEW.transaction_id AND t.type IN ('purchase','purchase_return','stock_adjustment')
     LIMIT 1;
END$$

DROP TRIGGER IF EXISTS `sau_purchase_lines_au`$$
CREATE TRIGGER `sau_purchase_lines_au` AFTER UPDATE ON `purchase_lines`
FOR EACH ROW
BEGIN
    IF NOT (OLD.quantity <=> NEW.quantity) OR NOT (OLD.purchase_price <=> NEW.purchase_price) OR
       NOT (OLD.purchase_price_inc_tax <=> NEW.purchase_price_inc_tax) OR NOT (OLD.discount_amount <=> NEW.discount_amount) OR
       NOT (OLD.discount_percent <=> NEW.discount_percent) OR NOT (OLD.item_tax <=> NEW.item_tax) OR
       NOT (OLD.tax_id <=> NEW.tax_id) OR NOT (OLD.quantity_returned <=> NEW.quantity_returned) OR
       NOT (OLD.deleted_at <=> NEW.deleted_at) OR NOT (OLD.new_deleted_at <=> NEW.new_deleted_at) THEN
        INSERT INTO sau_change_events
        (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, contact_id, product_id, variation_id, actor_user_id, old_data, new_data, occurred_at, created_at)
        SELECT t.business_id, t.location_id, t.store_id, 'purchase_lines', NEW.id, 'update', t.id, t.contact_id, NEW.product_id, NEW.variation_id, t.created_by,
               JSON_OBJECT('quantity',OLD.quantity,'purchase_price',OLD.purchase_price,'purchase_price_inc_tax',OLD.purchase_price_inc_tax,'discount_amount',OLD.discount_amount,'discount_percent',OLD.discount_percent,'item_tax',OLD.item_tax,'tax_id',OLD.tax_id,'quantity_returned',OLD.quantity_returned,'deleted_at',OLD.deleted_at,'new_deleted_at',OLD.new_deleted_at),
               JSON_OBJECT('quantity',NEW.quantity,'purchase_price',NEW.purchase_price,'purchase_price_inc_tax',NEW.purchase_price_inc_tax,'discount_amount',NEW.discount_amount,'discount_percent',NEW.discount_percent,'item_tax',NEW.item_tax,'tax_id',NEW.tax_id,'quantity_returned',NEW.quantity_returned,'deleted_at',NEW.deleted_at,'new_deleted_at',NEW.new_deleted_at),
               NOW(), NOW()
          FROM transactions t
         WHERE t.id = NEW.transaction_id AND t.type IN ('purchase','purchase_return','stock_adjustment')
         LIMIT 1;
    END IF;
END$$

DROP TRIGGER IF EXISTS `sau_purchase_lines_ad`$$
CREATE TRIGGER `sau_purchase_lines_ad` AFTER DELETE ON `purchase_lines`
FOR EACH ROW
BEGIN
    INSERT INTO sau_change_events
    (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, contact_id, product_id, variation_id, actor_user_id, old_data, new_data, occurred_at, created_at)
    SELECT t.business_id, t.location_id, t.store_id, 'purchase_lines', OLD.id, 'delete', t.id, t.contact_id, OLD.product_id, OLD.variation_id, t.created_by,
           JSON_OBJECT('quantity',OLD.quantity,'purchase_price',OLD.purchase_price,'purchase_price_inc_tax',OLD.purchase_price_inc_tax,'discount_amount',OLD.discount_amount,'discount_percent',OLD.discount_percent,'item_tax',OLD.item_tax,'tax_id',OLD.tax_id,'quantity_returned',OLD.quantity_returned,'deleted_at',OLD.deleted_at,'new_deleted_at',OLD.new_deleted_at),
           NULL, NOW(), NOW()
      FROM transactions t
     WHERE t.id = OLD.transaction_id AND t.type IN ('purchase','purchase_return','stock_adjustment')
     LIMIT 1;
END$$

DROP TRIGGER IF EXISTS `sau_stock_adjustment_lines_ai`$$
CREATE TRIGGER `sau_stock_adjustment_lines_ai` AFTER INSERT ON `stock_adjustment_lines`
FOR EACH ROW
BEGIN
    INSERT INTO sau_change_events
    (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, product_id, variation_id, actor_user_id, old_data, new_data, occurred_at, created_at)
    SELECT t.business_id, t.location_id, t.store_id, 'stock_adjustment_lines', NEW.id, 'insert', t.id, NEW.product_id, NEW.variation_id, t.created_by, NULL,
           JSON_OBJECT('quantity',NEW.quantity,'unit_price',NEW.unit_price,'type',NEW.type,'stock_adjustment_type',NEW.stock_adjustment_type), NOW(), NOW()
      FROM transactions t WHERE t.id=NEW.transaction_id AND t.type='stock_adjustment' LIMIT 1;
END$$

DROP TRIGGER IF EXISTS `sau_stock_adjustment_lines_au`$$
CREATE TRIGGER `sau_stock_adjustment_lines_au` AFTER UPDATE ON `stock_adjustment_lines`
FOR EACH ROW
BEGIN
    IF NOT (OLD.quantity <=> NEW.quantity) OR NOT (OLD.unit_price <=> NEW.unit_price) OR NOT (OLD.type <=> NEW.type) OR
       NOT (OLD.stock_adjustment_type <=> NEW.stock_adjustment_type) OR NOT (OLD.product_id <=> NEW.product_id) OR
       NOT (OLD.variation_id <=> NEW.variation_id) THEN
        INSERT INTO sau_change_events
        (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, product_id, variation_id, actor_user_id, old_data, new_data, occurred_at, created_at)
        SELECT t.business_id, t.location_id, t.store_id, 'stock_adjustment_lines', NEW.id, 'update', t.id, NEW.product_id, NEW.variation_id, t.created_by,
               JSON_OBJECT('quantity',OLD.quantity,'unit_price',OLD.unit_price,'type',OLD.type,'stock_adjustment_type',OLD.stock_adjustment_type),
               JSON_OBJECT('quantity',NEW.quantity,'unit_price',NEW.unit_price,'type',NEW.type,'stock_adjustment_type',NEW.stock_adjustment_type), NOW(), NOW()
          FROM transactions t WHERE t.id=NEW.transaction_id AND t.type='stock_adjustment' LIMIT 1;
    END IF;
END$$

DROP TRIGGER IF EXISTS `sau_stock_adjustment_lines_ad`$$
CREATE TRIGGER `sau_stock_adjustment_lines_ad` AFTER DELETE ON `stock_adjustment_lines`
FOR EACH ROW
BEGIN
    INSERT INTO sau_change_events
    (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, product_id, variation_id, actor_user_id, old_data, new_data, occurred_at, created_at)
    SELECT t.business_id, t.location_id, t.store_id, 'stock_adjustment_lines', OLD.id, 'delete', t.id, OLD.product_id, OLD.variation_id, t.created_by,
           JSON_OBJECT('quantity',OLD.quantity,'unit_price',OLD.unit_price,'type',OLD.type,'stock_adjustment_type',OLD.stock_adjustment_type), NULL, NOW(), NOW()
      FROM transactions t WHERE t.id=OLD.transaction_id AND t.type='stock_adjustment' LIMIT 1;
END$$

DROP TRIGGER IF EXISTS `sau_transaction_payments_ai`$$
CREATE TRIGGER `sau_transaction_payments_ai` AFTER INSERT ON `transaction_payments`
FOR EACH ROW
BEGIN
    INSERT INTO sau_change_events
    (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, payment_id, contact_id, account_id, actor_user_id, old_data, new_data, occurred_at, created_at)
    SELECT COALESCE(NEW.business_id,t.business_id,c.business_id), t.location_id, t.store_id, 'transaction_payments', NEW.id, 'insert', NEW.transaction_id, NEW.id, c.id, NEW.account_id, NEW.created_by,
           NULL,
           JSON_OBJECT('transaction_id',NEW.transaction_id,'payment_for',NEW.payment_for,'amount',NEW.amount,'method',NEW.method,'paid_on',NEW.paid_on,'account_id',NEW.account_id,'payment_ref_no',NEW.payment_ref_no,'deleted_at',NEW.deleted_at), NOW(), NOW()
      FROM contacts c
      LEFT JOIN transactions t ON t.id = NEW.transaction_id
     WHERE c.id = COALESCE(NEW.payment_for,t.contact_id) AND c.type IN ('supplier','both')
     LIMIT 1;
END$$

DROP TRIGGER IF EXISTS `sau_transaction_payments_au`$$
CREATE TRIGGER `sau_transaction_payments_au` AFTER UPDATE ON `transaction_payments`
FOR EACH ROW
BEGIN
    IF NOT (OLD.transaction_id <=> NEW.transaction_id) OR NOT (OLD.payment_for <=> NEW.payment_for) OR
       NOT (OLD.amount <=> NEW.amount) OR NOT (OLD.method <=> NEW.method) OR NOT (OLD.paid_on <=> NEW.paid_on) OR
       NOT (OLD.account_id <=> NEW.account_id) OR NOT (OLD.payment_ref_no <=> NEW.payment_ref_no) OR NOT (OLD.deleted_at <=> NEW.deleted_at) THEN
        INSERT INTO sau_change_events
        (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, payment_id, contact_id, account_id, actor_user_id, old_data, new_data, occurred_at, created_at)
        SELECT COALESCE(NEW.business_id,t.business_id,c.business_id), t.location_id, t.store_id, 'transaction_payments', NEW.id, 'update', NEW.transaction_id, NEW.id, c.id, NEW.account_id, NEW.created_by,
               JSON_OBJECT('transaction_id',OLD.transaction_id,'payment_for',OLD.payment_for,'amount',OLD.amount,'method',OLD.method,'paid_on',OLD.paid_on,'account_id',OLD.account_id,'payment_ref_no',OLD.payment_ref_no,'deleted_at',OLD.deleted_at),
               JSON_OBJECT('transaction_id',NEW.transaction_id,'payment_for',NEW.payment_for,'amount',NEW.amount,'method',NEW.method,'paid_on',NEW.paid_on,'account_id',NEW.account_id,'payment_ref_no',NEW.payment_ref_no,'deleted_at',NEW.deleted_at), NOW(), NOW()
          FROM contacts c
          LEFT JOIN transactions t ON t.id = NEW.transaction_id
         WHERE c.id = COALESCE(NEW.payment_for,t.contact_id) AND c.type IN ('supplier','both')
         LIMIT 1;
    END IF;
END$$

DROP TRIGGER IF EXISTS `sau_transaction_payments_ad`$$
CREATE TRIGGER `sau_transaction_payments_ad` AFTER DELETE ON `transaction_payments`
FOR EACH ROW
BEGIN
    INSERT INTO sau_change_events
    (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, payment_id, contact_id, account_id, actor_user_id, old_data, new_data, occurred_at, created_at)
    SELECT COALESCE(OLD.business_id,t.business_id,c.business_id), t.location_id, t.store_id, 'transaction_payments', OLD.id, 'delete', OLD.transaction_id, OLD.id, c.id, OLD.account_id, OLD.created_by,
           JSON_OBJECT('transaction_id',OLD.transaction_id,'payment_for',OLD.payment_for,'amount',OLD.amount,'method',OLD.method,'paid_on',OLD.paid_on,'account_id',OLD.account_id,'payment_ref_no',OLD.payment_ref_no,'deleted_at',OLD.deleted_at),
           NULL, NOW(), NOW()
      FROM contacts c
      LEFT JOIN transactions t ON t.id = OLD.transaction_id
     WHERE c.id = COALESCE(OLD.payment_for,t.contact_id) AND c.type IN ('supplier','both')
     LIMIT 1;
END$$

DROP TRIGGER IF EXISTS `sau_account_transactions_ai`$$
CREATE TRIGGER `sau_account_transactions_ai` AFTER INSERT ON `account_transactions`
FOR EACH ROW
BEGIN
    IF EXISTS (SELECT 1 FROM transactions tx WHERE tx.id = NEW.transaction_id AND tx.type IN ('purchase','purchase_return','stock_adjustment'))
       OR EXISTS (SELECT 1 FROM transaction_payments tp LEFT JOIN transactions txp ON txp.id=tp.transaction_id JOIN contacts c ON c.id=COALESCE(tp.payment_for,txp.contact_id) WHERE tp.id=NEW.transaction_payment_id AND c.type IN ('supplier','both')) THEN
        INSERT INTO sau_change_events
        (business_id, location_id, source_table, source_id, event_type, transaction_id, payment_id, account_id, actor_user_id, old_data, new_data, occurred_at, created_at)
        VALUES (NEW.business_id, NEW.location_id, 'account_transactions', NEW.id, 'insert', NEW.transaction_id, NEW.transaction_payment_id, NEW.account_id, NEW.created_by, NULL,
                JSON_OBJECT('account_id',NEW.account_id,'type',NEW.type,'amount',NEW.amount,'operation_date',NEW.operation_date,'transaction_id',NEW.transaction_id,'transaction_payment_id',NEW.transaction_payment_id,'reff_no',NEW.reff_no,'deleted_at',NEW.deleted_at,'new_deleted_at',NEW.new_deleted_at,'reversed',NEW.reversed), NOW(), NOW());
    END IF;
END$$

DROP TRIGGER IF EXISTS `sau_account_transactions_au`$$
CREATE TRIGGER `sau_account_transactions_au` AFTER UPDATE ON `account_transactions`
FOR EACH ROW
BEGIN
    IF NOT (OLD.account_id <=> NEW.account_id) OR NOT (OLD.type <=> NEW.type) OR NOT (OLD.amount <=> NEW.amount) OR
       NOT (OLD.operation_date <=> NEW.operation_date) OR NOT (OLD.transaction_id <=> NEW.transaction_id) OR
       NOT (OLD.transaction_payment_id <=> NEW.transaction_payment_id) OR NOT (OLD.reff_no <=> NEW.reff_no) OR
       NOT (OLD.deleted_at <=> NEW.deleted_at) OR NOT (OLD.new_deleted_at <=> NEW.new_deleted_at) OR NOT (OLD.reversed <=> NEW.reversed) THEN
        IF EXISTS (SELECT 1 FROM transactions tx WHERE tx.id = NEW.transaction_id AND tx.type IN ('purchase','purchase_return','stock_adjustment'))
           OR EXISTS (SELECT 1 FROM transaction_payments tp LEFT JOIN transactions txp ON txp.id=tp.transaction_id JOIN contacts c ON c.id=COALESCE(tp.payment_for,txp.contact_id) WHERE tp.id=NEW.transaction_payment_id AND c.type IN ('supplier','both')) THEN
            INSERT INTO sau_change_events
            (business_id, location_id, source_table, source_id, event_type, transaction_id, payment_id, account_id, actor_user_id, old_data, new_data, occurred_at, created_at)
            VALUES (NEW.business_id, NEW.location_id, 'account_transactions', NEW.id, 'update', NEW.transaction_id, NEW.transaction_payment_id, NEW.account_id, NEW.created_by,
                    JSON_OBJECT('account_id',OLD.account_id,'type',OLD.type,'amount',OLD.amount,'operation_date',OLD.operation_date,'transaction_id',OLD.transaction_id,'transaction_payment_id',OLD.transaction_payment_id,'reff_no',OLD.reff_no,'deleted_at',OLD.deleted_at,'new_deleted_at',OLD.new_deleted_at,'reversed',OLD.reversed),
                    JSON_OBJECT('account_id',NEW.account_id,'type',NEW.type,'amount',NEW.amount,'operation_date',NEW.operation_date,'transaction_id',NEW.transaction_id,'transaction_payment_id',NEW.transaction_payment_id,'reff_no',NEW.reff_no,'deleted_at',NEW.deleted_at,'new_deleted_at',NEW.new_deleted_at,'reversed',NEW.reversed), NOW(), NOW());
        END IF;
    END IF;
END$$

DROP TRIGGER IF EXISTS `sau_account_transactions_ad`$$
CREATE TRIGGER `sau_account_transactions_ad` AFTER DELETE ON `account_transactions`
FOR EACH ROW
BEGIN
    IF EXISTS (SELECT 1 FROM transactions tx WHERE tx.id = OLD.transaction_id AND tx.type IN ('purchase','purchase_return','stock_adjustment'))
       OR EXISTS (SELECT 1 FROM transaction_payments tp LEFT JOIN transactions txp ON txp.id=tp.transaction_id JOIN contacts c ON c.id=COALESCE(tp.payment_for,txp.contact_id) WHERE tp.id=OLD.transaction_payment_id AND c.type IN ('supplier','both')) THEN
        INSERT INTO sau_change_events
        (business_id, location_id, source_table, source_id, event_type, transaction_id, payment_id, account_id, actor_user_id, old_data, new_data, occurred_at, created_at)
        VALUES (OLD.business_id, OLD.location_id, 'account_transactions', OLD.id, 'delete', OLD.transaction_id, OLD.transaction_payment_id, OLD.account_id, OLD.created_by,
                JSON_OBJECT('account_id',OLD.account_id,'type',OLD.type,'amount',OLD.amount,'operation_date',OLD.operation_date,'transaction_id',OLD.transaction_id,'transaction_payment_id',OLD.transaction_payment_id,'reff_no',OLD.reff_no,'deleted_at',OLD.deleted_at,'new_deleted_at',OLD.new_deleted_at,'reversed',OLD.reversed), NULL, NOW(), NOW());
    END IF;
END$$

DROP TRIGGER IF EXISTS `sau_contact_ledgers_ai`$$
CREATE TRIGGER `sau_contact_ledgers_ai` AFTER INSERT ON `contact_ledgers`
FOR EACH ROW
BEGIN
    INSERT INTO sau_change_events
    (business_id, source_table, source_id, event_type, transaction_id, payment_id, contact_id, actor_user_id, old_data, new_data, occurred_at, created_at)
    SELECT COALESCE(NEW.business_id,c.business_id), 'contact_ledgers', NEW.id, 'insert', NEW.transaction_id, NEW.transaction_payment_id, NEW.contact_id, NEW.created_by, NULL,
           JSON_OBJECT('contact_id',NEW.contact_id,'type',NEW.type,'amount',NEW.amount,'operation_date',NEW.operation_date,'transaction_id',NEW.transaction_id,'transaction_payment_id',NEW.transaction_payment_id,'reff_no',NEW.reff_no,'deleted_at',NEW.deleted_at,'reversed',NEW.reversed), NOW(), NOW()
      FROM contacts c WHERE c.id=NEW.contact_id AND c.type IN ('supplier','both')
       AND (EXISTS (SELECT 1 FROM transactions tx WHERE tx.id=NEW.transaction_id AND tx.type IN ('purchase','purchase_return','stock_adjustment'))
            OR EXISTS (SELECT 1 FROM transaction_payments tp WHERE tp.id=NEW.transaction_payment_id AND tp.deleted_at IS NULL)) LIMIT 1;
END$$

DROP TRIGGER IF EXISTS `sau_contact_ledgers_au`$$
CREATE TRIGGER `sau_contact_ledgers_au` AFTER UPDATE ON `contact_ledgers`
FOR EACH ROW
BEGIN
    IF NOT (OLD.contact_id <=> NEW.contact_id) OR NOT (OLD.type <=> NEW.type) OR NOT (OLD.amount <=> NEW.amount) OR
       NOT (OLD.operation_date <=> NEW.operation_date) OR NOT (OLD.transaction_id <=> NEW.transaction_id) OR
       NOT (OLD.transaction_payment_id <=> NEW.transaction_payment_id) OR NOT (OLD.reff_no <=> NEW.reff_no) OR
       NOT (OLD.deleted_at <=> NEW.deleted_at) OR NOT (OLD.reversed <=> NEW.reversed) THEN
        INSERT INTO sau_change_events
        (business_id, source_table, source_id, event_type, transaction_id, payment_id, contact_id, actor_user_id, old_data, new_data, occurred_at, created_at)
        SELECT COALESCE(NEW.business_id,c.business_id), 'contact_ledgers', NEW.id, 'update', NEW.transaction_id, NEW.transaction_payment_id, NEW.contact_id, NEW.created_by,
               JSON_OBJECT('contact_id',OLD.contact_id,'type',OLD.type,'amount',OLD.amount,'operation_date',OLD.operation_date,'transaction_id',OLD.transaction_id,'transaction_payment_id',OLD.transaction_payment_id,'reff_no',OLD.reff_no,'deleted_at',OLD.deleted_at,'reversed',OLD.reversed),
               JSON_OBJECT('contact_id',NEW.contact_id,'type',NEW.type,'amount',NEW.amount,'operation_date',NEW.operation_date,'transaction_id',NEW.transaction_id,'transaction_payment_id',NEW.transaction_payment_id,'reff_no',NEW.reff_no,'deleted_at',NEW.deleted_at,'reversed',NEW.reversed), NOW(), NOW()
          FROM contacts c WHERE c.id=NEW.contact_id AND c.type IN ('supplier','both')
           AND (EXISTS (SELECT 1 FROM transactions tx WHERE tx.id=NEW.transaction_id AND tx.type IN ('purchase','purchase_return','stock_adjustment'))
                OR EXISTS (SELECT 1 FROM transaction_payments tp WHERE tp.id=NEW.transaction_payment_id AND tp.deleted_at IS NULL)) LIMIT 1;
    END IF;
END$$

DROP TRIGGER IF EXISTS `sau_contact_ledgers_ad`$$
CREATE TRIGGER `sau_contact_ledgers_ad` AFTER DELETE ON `contact_ledgers`
FOR EACH ROW
BEGIN
    INSERT INTO sau_change_events
    (business_id, source_table, source_id, event_type, transaction_id, payment_id, contact_id, actor_user_id, old_data, new_data, occurred_at, created_at)
    SELECT COALESCE(OLD.business_id,c.business_id), 'contact_ledgers', OLD.id, 'delete', OLD.transaction_id, OLD.transaction_payment_id, OLD.contact_id, OLD.created_by,
           JSON_OBJECT('contact_id',OLD.contact_id,'type',OLD.type,'amount',OLD.amount,'operation_date',OLD.operation_date,'transaction_id',OLD.transaction_id,'transaction_payment_id',OLD.transaction_payment_id,'reff_no',OLD.reff_no,'deleted_at',OLD.deleted_at,'reversed',OLD.reversed), NULL, NOW(), NOW()
      FROM contacts c WHERE c.id=OLD.contact_id AND c.type IN ('supplier','both')
       AND (EXISTS (SELECT 1 FROM transactions tx WHERE tx.id=OLD.transaction_id AND tx.type IN ('purchase','purchase_return','stock_adjustment'))
            OR EXISTS (SELECT 1 FROM transaction_payments tp WHERE tp.id=OLD.transaction_payment_id AND tp.deleted_at IS NULL)) LIMIT 1;
END$$

DELIMITER ;

SET FOREIGN_KEY_CHECKS=1;

-- Verification
SELECT 'Simple Audit tenant installation completed' AS status;
SELECT COUNT(*) AS sau_stock_snapshot_rows FROM sau_stock_snapshots;
