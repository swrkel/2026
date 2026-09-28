-- Membership New - Membership Settings performance index
-- Safe to run on the active Membership New database. No database name is hard-coded.
-- Adds the index only when it is missing.

SET @mn_index_exists := (
    SELECT COUNT(1)
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'mn_regions'
      AND index_name = 'mn_regions_business_deleted_date_id_index'
);

SET @mn_sql := IF(
    @mn_index_exists = 0,
    'ALTER TABLE `mn_regions` ADD INDEX `mn_regions_business_deleted_date_id_index` (`business_id`, `deleted_at`, `date`, `id`)',
    'SELECT 1'
);

PREPARE mn_stmt FROM @mn_sql;
EXECUTE mn_stmt;
DEALLOCATE PREPARE mn_stmt;
