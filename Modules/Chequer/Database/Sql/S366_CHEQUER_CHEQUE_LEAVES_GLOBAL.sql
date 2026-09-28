-- S366 - Chequer Module cheque leaf control.
-- Run inside each tenant database. No hard-coded database name.

SET @db_name := DATABASE();

CREATE TABLE IF NOT EXISTS `cheq_cheque_leaves` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NOT NULL,
  `cheq_cheque_book_id` BIGINT UNSIGNED NOT NULL,
  `cheque_no` VARCHAR(100) NOT NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'available',
  `cheq_cheque_id` BIGINT UNSIGNED NULL,
  `issued_at` TIMESTAMP NULL,
  `cancelled_at` TIMESTAMP NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cheq_leaves_book_cheque_unique` (`cheq_cheque_book_id`, `cheque_no`),
  KEY `cheq_leaves_business_id_index` (`business_id`),
  KEY `cheq_leaves_status_index` (`status`),
  KEY `cheq_leaves_cheq_cheque_id_index` (`cheq_cheque_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @column_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'cheq_cheques'
      AND COLUMN_NAME = 'cheq_cheque_leaf_id'
);
SET @sql := IF(@column_exists = 0,
    'ALTER TABLE `cheq_cheques` ADD COLUMN `cheq_cheque_leaf_id` BIGINT UNSIGNED NULL AFTER `cheq_cheque_book_id`',
    'SELECT "cheq_cheques.cheq_cheque_leaf_id already exists" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @index_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'cheq_cheques'
      AND INDEX_NAME = 'cheq_cheques_leaf_id_index'
);
SET @sql := IF(@index_exists = 0,
    'ALTER TABLE `cheq_cheques` ADD INDEX `cheq_cheques_leaf_id_index` (`cheq_cheque_leaf_id`)',
    'SELECT "cheq_cheques_leaf_id_index already exists" AS message'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Create missing leaves for existing cheque books. Supports ranges up to 10,000 leaves per book.
INSERT IGNORE INTO `cheq_cheque_leaves` (`business_id`, `cheq_cheque_book_id`, `cheque_no`, `status`, `created_at`, `updated_at`)
SELECT
    cb.`business_id`,
    cb.`id`,
    CAST(cb.`start_no` + seq.n AS CHAR),
    CASE
        WHEN (cb.`start_no` + seq.n) < cb.`next_no` THEN 'issued'
        ELSE 'available'
    END,
    NOW(),
    NOW()
FROM `cheq_cheque_books` cb
JOIN (
    SELECT ones.n + tens.n * 10 + hundreds.n * 100 + thousands.n * 1000 AS n
    FROM
        (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) ones
        CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) tens
        CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) hundreds
        CROSS JOIN (SELECT 0 n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) thousands
) seq ON cb.`start_no` + seq.n <= cb.`end_no`
WHERE cb.`start_no` IS NOT NULL
  AND cb.`end_no` IS NOT NULL
  AND cb.`end_no` >= cb.`start_no`;
