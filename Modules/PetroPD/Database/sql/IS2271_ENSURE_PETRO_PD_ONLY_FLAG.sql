-- IS2271 - Petro PD only operator compatibility flag
-- Safe to run repeatedly on a selected TENANT database.
-- Does not delete or modify existing operator records.

SET @is2271_sql := (
    SELECT CASE
        WHEN NOT EXISTS (
            SELECT 1
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pump_operators'
        ) THEN
            'SELECT ''IS2271 warning: pump_operators table does not exist in the selected database.'' AS is2271_warning'
        WHEN EXISTS (
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pump_operators'
              AND COLUMN_NAME = 'is_petro_pd_only'
        ) THEN
            'SELECT ''IS2271: is_petro_pd_only already exists - no schema change required.'' AS is2271_status'
        ELSE
            'ALTER TABLE `pump_operators` ADD COLUMN `is_petro_pd_only` TINYINT(1) NOT NULL DEFAULT 0'
    END
);

PREPARE is2271_stmt FROM @is2271_sql;
EXECUTE is2271_stmt;
DEALLOCATE PREPARE is2271_stmt;

SELECT 'IS2271 PetroPD schema check completed.' AS is2271_status;
