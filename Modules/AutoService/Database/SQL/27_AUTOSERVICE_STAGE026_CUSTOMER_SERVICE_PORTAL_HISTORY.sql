/*
 AutoService Stage 026 - Customer Service Portal, Current Bill and Parts/Accessories History
 Execute against each tenant database that uses the Auto Service module.
 Safe to run repeatedly. No database name is hard-coded.
*/

SET @db_name := DATABASE();

DROP PROCEDURE IF EXISTS autoservice_stage026_add_column;
DELIMITER $$
CREATE PROCEDURE autoservice_stage026_add_column(IN p_table VARCHAR(100), IN p_column VARCHAR(100), IN p_definition TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = p_table)
       AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = p_table AND COLUMN_NAME = p_column) THEN
        SET @sql := CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

DROP PROCEDURE IF EXISTS autoservice_stage026_add_index;
DELIMITER $$
CREATE PROCEDURE autoservice_stage026_add_index(IN p_table VARCHAR(100), IN p_index VARCHAR(100), IN p_columns TEXT)
BEGIN
    IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = p_table)
       AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = p_table AND INDEX_NAME = p_index) THEN
        SET @sql := CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL autoservice_stage026_add_column('auto_service_job_lines', 'discount_amount', '`discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `unit_price`');
CALL autoservice_stage026_add_column('auto_service_job_lines', 'tax_amount', '`tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `discount_amount`');
CALL autoservice_stage026_add_index('auto_service_job_lines', 'idx_asjl_customer_parts_filter', '`job_id`, `line_type`, `product_id`');

CALL autoservice_stage026_add_column('auto_service_invoice_lines', 'discount_amount', '`discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `unit_price`');
CALL autoservice_stage026_add_column('auto_service_invoice_lines', 'tax_amount', '`tax_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `discount_amount`');
CALL autoservice_stage026_add_index('auto_service_invoice_lines', 'idx_asil_customer_parts_filter', '`invoice_id`, `line_type`, `product_id`');

CALL autoservice_stage026_add_column('auto_service_part_movements', 'unit_price', '`unit_price` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `quantity`');
CALL autoservice_stage026_add_column('auto_service_part_movements', 'discount_amount', '`discount_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `unit_price`');
CALL autoservice_stage026_add_index('auto_service_part_movements', 'idx_aspm_customer_parts_filter', '`job_id`, `movement_date`, `product_id`');

CALL autoservice_stage026_add_column('auto_service_jobs', 'customer_visible_note', '`customer_visible_note` TEXT NULL');
CALL autoservice_stage026_add_index('auto_service_jobs', 'idx_as_jobs_customer_portal', '`business_id`, `contact_id`, `vehicle_id`, `job_date`');
CALL autoservice_stage026_add_index('auto_service_invoices', 'idx_as_inv_customer_portal', '`business_id`, `contact_id`, `vehicle_id`, `job_id`, `invoice_date`');

INSERT INTO auto_service_settings (`business_id`, `key`, `value`, `created_at`, `updated_at`)
SELECT NULL, 'enable_customer_current_invoice_view', '1', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'auto_service_settings')
  AND NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key` = 'enable_customer_current_invoice_view' AND business_id IS NULL);

INSERT INTO auto_service_settings (`business_id`, `key`, `value`, `created_at`, `updated_at`)
SELECT NULL, 'allow_customer_feedback', '1', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'auto_service_settings')
  AND NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key` = 'allow_customer_feedback' AND business_id IS NULL);

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.customer_portal.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.customer_portal.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.customer_portal.parts_history.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.customer_portal.parts_history.view');

DROP PROCEDURE IF EXISTS autoservice_stage026_add_column;
DROP PROCEDURE IF EXISTS autoservice_stage026_add_index;

SELECT 'AutoService Stage 026 Customer Service Portal SQL completed' AS status;
