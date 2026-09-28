-- ============================================================================
-- Pumper Dashboard-New / Petro PD-New operator compatibility repair
-- Safe to run repeatedly in each affected tenant database.
-- Purpose:
--   1. Keep canonical pd_operator_id and legacy petro_pd_operator_id aligned.
--   2. Repair the old unique key values that produced duplicate entry 'x-0'.
--   3. Ensure the canonical business/operator unique index exists.
-- No records are deleted.
-- ============================================================================

SET NAMES utf8mb4;

SET @has_table := (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'pone_pd_operators'
);

SET @has_pd_operator_id := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'pone_pd_operators'
      AND column_name = 'pd_operator_id'
);

SET @has_legacy_operator_id := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'pone_pd_operators'
      AND column_name = 'petro_pd_operator_id'
);

SET @sql := IF(
    @has_table = 1 AND @has_pd_operator_id = 0,
    'ALTER TABLE `pone_pd_operators` ADD COLUMN `pd_operator_id` INT UNSIGNED NULL',
    'SELECT ''pd_operator_id already exists or table is absent'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_pd_operator_id := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'pone_pd_operators'
      AND column_name = 'pd_operator_id'
);

SET @sql := IF(
    @has_table = 1 AND @has_pd_operator_id = 1 AND @has_legacy_operator_id = 1,
    'UPDATE `pone_pd_operators`
       SET `pd_operator_id` = `petro_pd_operator_id`
     WHERE (`pd_operator_id` IS NULL OR `pd_operator_id` = 0)
       AND `petro_pd_operator_id` > 0',
    'SELECT ''canonical backfill not required'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_table = 1 AND @has_pd_operator_id = 1 AND @has_legacy_operator_id = 1,
    'UPDATE `pone_pd_operators`
       SET `petro_pd_operator_id` = `pd_operator_id`
     WHERE (`petro_pd_operator_id` IS NULL OR `petro_pd_operator_id` = 0)
       AND `pd_operator_id` > 0',
    'SELECT ''legacy backfill not required'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add the canonical unique key only when it does not already exist and the
-- current data does not contain duplicate business/operator pairs.
SET @canonical_index_exists := (
    SELECT COUNT(*)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'pone_pd_operators'
      AND index_name = 'pone_pd_operator_business_uq'
);

SET @canonical_duplicates := IF(
    @has_table = 1 AND @has_pd_operator_id = 1,
    (
        SELECT COUNT(*)
        FROM (
            SELECT business_id, pd_operator_id
            FROM pone_pd_operators
            WHERE pd_operator_id IS NOT NULL AND pd_operator_id > 0
            GROUP BY business_id, pd_operator_id
            HAVING COUNT(*) > 1
        ) duplicate_groups
    ),
    0
);

SET @sql := IF(
    @has_table = 1
    AND @has_pd_operator_id = 1
    AND @canonical_index_exists = 0
    AND @canonical_duplicates = 0,
    'ALTER TABLE `pone_pd_operators`
       ADD UNIQUE KEY `pone_pd_operator_business_uq` (`business_id`, `pd_operator_id`)',
    'SELECT ''canonical unique key already exists or duplicate rows require review'' AS message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT DATABASE() AS active_tenant_database;

SET @sql := IF(
    @has_table = 1 AND @has_pd_operator_id = 1 AND @has_legacy_operator_id = 1,
    'SELECT COUNT(*) AS unresolved_legacy_zero_operator_ids
       FROM `pone_pd_operators`
      WHERE `pd_operator_id` > 0
        AND (`petro_pd_operator_id` IS NULL OR `petro_pd_operator_id` = 0)',
    'SELECT 0 AS unresolved_legacy_zero_operator_ids'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_table = 1 AND @has_pd_operator_id = 1 AND @has_legacy_operator_id = 1,
    'SELECT `business_id`, `pd_operator_id`, `petro_pd_operator_id`, COUNT(*) AS row_count
       FROM `pone_pd_operators`
      GROUP BY `business_id`, `pd_operator_id`, `petro_pd_operator_id`
     HAVING COUNT(*) > 1',
    'SELECT NULL AS business_id, NULL AS pd_operator_id, NULL AS petro_pd_operator_id, 0 AS row_count WHERE 1 = 0'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT 'PONE PD operator compatibility repair completed.' AS message;
