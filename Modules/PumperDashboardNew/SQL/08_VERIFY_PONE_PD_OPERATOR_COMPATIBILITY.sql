SET NAMES utf8mb4;

SELECT DATABASE() AS active_tenant_database;

SET @has_table := (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators'
);
SET @has_pd_operator_id := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators' AND column_name = 'pd_operator_id'
);
SET @has_legacy_operator_id := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'pone_pd_operators' AND column_name = 'petro_pd_operator_id'
);

SELECT IF(@has_table = 1, 'OK', 'MISSING') AS pone_pd_operators_table;
SELECT IF(@has_pd_operator_id = 1, 'OK', 'MISSING') AS pd_operator_id_column;
SELECT IF(@has_legacy_operator_id = 1, 'OK', 'NOT_REQUIRED') AS legacy_operator_id_column;

SET @sql := IF(
    @has_table = 1 AND @has_pd_operator_id = 1 AND @has_legacy_operator_id = 1,
    'SELECT COUNT(*) AS unresolved_operator_id_rows
       FROM `pone_pd_operators`
      WHERE (`pd_operator_id` IS NULL OR `pd_operator_id` = 0)
         OR (`petro_pd_operator_id` IS NULL OR `petro_pd_operator_id` = 0)',
    IF(
        @has_table = 1 AND @has_pd_operator_id = 1,
        'SELECT COUNT(*) AS unresolved_operator_id_rows
           FROM `pone_pd_operators`
          WHERE (`pd_operator_id` IS NULL OR `pd_operator_id` = 0)',
        'SELECT 1 AS unresolved_operator_id_rows'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @has_table = 1 AND @has_pd_operator_id = 1,
    'SELECT COUNT(*) AS duplicate_business_operator_groups
       FROM (
            SELECT `business_id`, `pd_operator_id`
              FROM `pone_pd_operators`
             WHERE `pd_operator_id` IS NOT NULL AND `pd_operator_id` > 0
             GROUP BY `business_id`, `pd_operator_id`
            HAVING COUNT(*) > 1
       ) duplicate_groups',
    'SELECT 1 AS duplicate_business_operator_groups'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SELECT IF(EXISTS(
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'pone_pd_operators'
      AND index_name = 'pone_pd_operator_business_uq'
), 'OK', 'MISSING') AS canonical_unique_index;
