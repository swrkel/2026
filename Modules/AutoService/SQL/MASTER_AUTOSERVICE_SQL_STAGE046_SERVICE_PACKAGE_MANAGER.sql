-- AUTO SERVICE STAGE 046 - SERVICE PACKAGE MANAGER
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS auto_service_package_categories (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 business_id BIGINT UNSIGNED NULL,
 location_id BIGINT UNSIGNED NULL,
 code VARCHAR(50) NULL,
 name VARCHAR(150) NOT NULL,
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 deleted_at TIMESTAMP NULL,
 PRIMARY KEY(id),
 UNIQUE KEY as_pkg_cat_business_code_uq(business_id,code),
 KEY idx_as_pkg_cat_business(business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_job_packages (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 business_id BIGINT UNSIGNED NULL, location_id BIGINT UNSIGNED NULL,
 job_id BIGINT UNSIGNED NOT NULL, package_id BIGINT UNSIGNED NOT NULL,
 package_name VARCHAR(190) NOT NULL, package_price DECIMAL(22,4) NOT NULL DEFAULT 0,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY idx_as_job_pkg_job(job_id), KEY idx_as_job_pkg_package(package_id), KEY idx_as_job_pkg_business(business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auto_service_package_stock_movements (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 business_id BIGINT UNSIGNED NULL, location_id BIGINT UNSIGNED NULL,
 invoice_id BIGINT UNSIGNED NULL, job_id BIGINT UNSIGNED NULL, invoice_line_id BIGINT UNSIGNED NULL,
 package_id BIGINT UNSIGNED NULL, product_id BIGINT UNSIGNED NULL, variation_id BIGINT UNSIGNED NULL,
 movement_type VARCHAR(50) NOT NULL, quantity DECIMAL(22,4) NOT NULL,
 unit_price DECIMAL(22,4) NOT NULL DEFAULT 0, reference_no VARCHAR(100) NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY idx_as_pkg_stock_invoice(invoice_id), KEY idx_as_pkg_stock_job(job_id),
 KEY idx_as_pkg_stock_package(package_id), KEY idx_as_pkg_stock_variation(variation_id),
 KEY idx_as_pkg_stock_business_date(business_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$
DROP PROCEDURE IF EXISTS autoservice_stage046_add_column$$
CREATE PROCEDURE autoservice_stage046_add_column(IN p_table VARCHAR(190),IN p_column VARCHAR(190),IN p_definition TEXT)
proc: BEGIN
 IF NOT EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=p_table) THEN LEAVE proc; END IF;
 IF EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=p_table AND column_name=p_column) THEN LEAVE proc; END IF;
 SET @q=CONCAT('ALTER TABLE `',p_table,'` ADD COLUMN `',p_column,'` ',p_definition);
 PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
END$$
DELIMITER ;

CALL autoservice_stage046_add_column('auto_service_service_packages','category_id','BIGINT UNSIGNED NULL');
CALL autoservice_stage046_add_column('auto_service_service_packages','vehicle_brand','VARCHAR(120) NULL');
CALL autoservice_stage046_add_column('auto_service_service_packages','vehicle_model','VARCHAR(120) NULL');
CALL autoservice_stage046_add_column('auto_service_service_packages','estimated_minutes','INT NULL');
CALL autoservice_stage046_add_column('auto_service_service_packages','warranty_days','INT NULL');
CALL autoservice_stage046_add_column('auto_service_service_packages','non_stock_amount','DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL autoservice_stage046_add_column('auto_service_service_packages','subtotal_amount','DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL autoservice_stage046_add_column('auto_service_service_packages','package_discount','DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL autoservice_stage046_add_column('auto_service_service_packages','package_tax','DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL autoservice_stage046_add_column('auto_service_service_packages','selling_price','DECIMAL(22,4) NOT NULL DEFAULT 0');

CALL autoservice_stage046_add_column('auto_service_package_lines','location_id','BIGINT UNSIGNED NULL');
CALL autoservice_stage046_add_column('auto_service_package_lines','component_type','VARCHAR(40) NULL');
CALL autoservice_stage046_add_column('auto_service_package_lines','variation_id','BIGINT UNSIGNED NULL');
CALL autoservice_stage046_add_column('auto_service_package_lines','item_name','VARCHAR(255) NULL');
CALL autoservice_stage046_add_column('auto_service_package_lines','unit_name','VARCHAR(50) NULL');
CALL autoservice_stage046_add_column('auto_service_package_lines','discount_amount','DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL autoservice_stage046_add_column('auto_service_package_lines','tax_amount','DECIMAL(22,4) NOT NULL DEFAULT 0');
CALL autoservice_stage046_add_column('auto_service_package_lines','is_optional','TINYINT(1) NOT NULL DEFAULT 0');
CALL autoservice_stage046_add_column('auto_service_package_lines','is_stock_item','TINYINT(1) NOT NULL DEFAULT 0');
CALL autoservice_stage046_add_column('auto_service_package_lines','sort_order','INT NOT NULL DEFAULT 0');

CALL autoservice_stage046_add_column('auto_service_job_lines','component_type','VARCHAR(40) NULL');
CALL autoservice_stage046_add_column('auto_service_job_lines','package_id','BIGINT UNSIGNED NULL');
CALL autoservice_stage046_add_column('auto_service_job_lines','package_line_id','BIGINT UNSIGNED NULL');
CALL autoservice_stage046_add_column('auto_service_job_lines','variation_id','BIGINT UNSIGNED NULL');
CALL autoservice_stage046_add_column('auto_service_job_lines','is_stock_item','TINYINT(1) NOT NULL DEFAULT 0');

CALL autoservice_stage046_add_column('auto_service_invoice_lines','component_type','VARCHAR(40) NULL');
CALL autoservice_stage046_add_column('auto_service_invoice_lines','package_id','BIGINT UNSIGNED NULL');
CALL autoservice_stage046_add_column('auto_service_invoice_lines','package_line_id','BIGINT UNSIGNED NULL');
CALL autoservice_stage046_add_column('auto_service_invoice_lines','variation_id','BIGINT UNSIGNED NULL');
CALL autoservice_stage046_add_column('auto_service_invoice_lines','is_stock_item','TINYINT(1) NOT NULL DEFAULT 0');

CALL autoservice_stage046_add_column('auto_service_invoices','posted_at','DATETIME NULL');
CALL autoservice_stage046_add_column('auto_service_invoices','stock_posted_at','DATETIME NULL');
CALL autoservice_stage046_add_column('auto_service_invoices','posted_by','BIGINT UNSIGNED NULL');
DROP PROCEDURE IF EXISTS autoservice_stage046_add_column;

INSERT INTO permissions(name,guard_name,created_at,updated_at)
SELECT x.name,'web',NOW(),NOW() FROM (
 SELECT 'autoservice.packages.view' name UNION ALL
 SELECT 'autoservice.packages.manage' UNION ALL
 SELECT 'autoservice.packages.load_to_job' UNION ALL
 SELECT 'autoservice.invoices.post_stock' UNION ALL
 SELECT 'autoservice.reports.package_usage' UNION ALL
 SELECT 'autoservice.reports.package_profitability' UNION ALL
 SELECT 'autoservice.reports.package_stock_consumption'
) x WHERE NOT EXISTS(SELECT 1 FROM permissions p WHERE p.name=x.name);

INSERT INTO auto_service_package_categories(business_id,location_id,code,name,is_active,created_at,updated_at)
SELECT NULL,NULL,x.code,x.name,1,NOW(),NOW() FROM (
 SELECT 'periodic_service' code,'Periodic Service' name UNION ALL
 SELECT 'engine','Engine' UNION ALL SELECT 'brakes','Brake' UNION ALL
 SELECT 'electrical','Electrical' UNION ALL SELECT 'ac','Air Conditioning' UNION ALL
 SELECT 'detailing','Detailing' UNION ALL SELECT 'fleet','Fleet Service'
) x WHERE NOT EXISTS(SELECT 1 FROM auto_service_package_categories c WHERE c.business_id IS NULL AND c.code=x.code);
