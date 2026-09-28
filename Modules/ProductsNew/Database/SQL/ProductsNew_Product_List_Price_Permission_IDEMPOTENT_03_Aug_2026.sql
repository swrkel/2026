-- Products New - List Products price visibility permission
-- Date: 03 Aug 2026
-- Run in every tenant database where Products New is enabled.
-- Safe to run repeatedly. No table, column, role or role-permission duplicate is created.

SET @pn_permission_name := 'products_new.purchase_price.view';
SET @pn_guard_name := 'web';

-- Add the permission used by User Management > Role Add/Edit.
SET @pn_has_permissions_table := (
    SELECT CASE WHEN EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'permissions'
    ) AND (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'permissions'
          AND column_name IN ('name', 'guard_name', 'created_at', 'updated_at')
    ) = 4 THEN 1 ELSE 0 END
);

SET @pn_sql := IF(
    @pn_has_permissions_table > 0,
    'INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
     SELECT ''products_new.purchase_price.view'', ''web'', NOW(), NOW()
     WHERE NOT EXISTS (
         SELECT 1
         FROM `permissions`
         WHERE `name` = ''products_new.purchase_price.view''
           AND `guard_name` = ''web''
     )',
    'SELECT ''permissions table not found; Products New purchase-price permission was skipped'' AS products_new_note'
);

PREPARE pn_stmt FROM @pn_sql;
EXECUTE pn_stmt;
DEALLOCATE PREPARE pn_stmt;

-- Add the optional Products New permission catalogue entry when that catalogue
-- exists in the installed version. This supplies the friendly Role-page label.
SET @pn_has_permission_catalogue := (
    SELECT CASE WHEN EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'products_new_permissions'
    ) AND (
        SELECT COUNT(*)
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'products_new_permissions'
          AND column_name IN ('name', 'display_name', 'module', 'created_at', 'updated_at')
    ) = 5 THEN 1 ELSE 0 END
);

SET @pn_sql := IF(
    @pn_has_permission_catalogue > 0,
    'INSERT INTO `products_new_permissions`
        (`name`, `display_name`, `module`, `created_at`, `updated_at`)
     SELECT
        ''products_new.purchase_price.view'',
        ''View Purchase Price'',
        ''Products New'',
        NOW(),
        NOW()
     WHERE NOT EXISTS (
         SELECT 1
         FROM `products_new_permissions`
         WHERE `name` = ''products_new.purchase_price.view''
     )',
    'SELECT ''products_new_permissions catalogue not found; optional label insert was skipped'' AS products_new_note'
);

PREPARE pn_stmt FROM @pn_sql;
EXECUTE pn_stmt;
DEALLOCATE PREPARE pn_stmt;

-- Verification result.
SET @pn_sql := IF(
    @pn_has_permissions_table > 0,
    'SELECT `name`, `guard_name`
     FROM `permissions`
     WHERE `name` = ''products_new.purchase_price.view''
       AND `guard_name` = ''web''',
    'SELECT ''Permission could not be verified because permissions table is missing'' AS products_new_note'
);

PREPARE pn_stmt FROM @pn_sql;
EXECUTE pn_stmt;
DEALLOCATE PREPARE pn_stmt;

SET @pn_permission_name := NULL;
SET @pn_guard_name := NULL;
SET @pn_has_permissions_table := NULL;
SET @pn_has_permission_catalogue := NULL;
SET @pn_sql := NULL;
