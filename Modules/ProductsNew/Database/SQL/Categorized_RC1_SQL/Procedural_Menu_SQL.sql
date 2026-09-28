-- Statement 86
-- Optional menu registration table support. Safe for installations using module_menu_items.
SET @products_new_menu_table_exists := (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'module_menu_items'
);

-- Statement 87
SET @products_new_sql := IF(@products_new_menu_table_exists > 0,
"INSERT INTO module_menu_items (module, label, route, permission, sort_order, is_active, created_at, updated_at)
 SELECT 'ProductsNew', 'Deployment Readiness', 'products-new.deployment-readiness.index', 'products_new.deployment_readiness', 990, 1, NOW(), NOW()
 WHERE NOT EXISTS (SELECT 1 FROM module_menu_items WHERE route = 'products-new.deployment-readiness.index')",
"SELECT 'module_menu_items table not found - skipped Products New Deployment Readiness menu insert' AS products_new_stage014_note"
);

-- Statement 88
PREPARE stmt FROM @products_new_sql;

-- Statement 89
EXECUTE stmt;

-- Statement 90
DEALLOCATE PREPARE stmt;
