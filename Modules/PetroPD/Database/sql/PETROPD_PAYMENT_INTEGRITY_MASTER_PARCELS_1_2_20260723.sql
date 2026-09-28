/*
 PETROPD PAYMENT INTEGRITY - TENANT MASTER SQL
 23 July 2026

 Run on EACH tenant database after a full backup.
 Idempotent schema/index helpers are used. Existing financial rows are not deleted.
 Ambiguous legacy links are intentionally not guessed.
*/

SET @OLD_SQL_SAFE_UPDATES := @@SQL_SAFE_UPDATES;
SET SQL_SAFE_UPDATES = 0;

DELIMITER $$
DROP PROCEDURE IF EXISTS pd_add_column_if_missing$$
CREATE PROCEDURE pd_add_column_if_missing(
    IN p_table VARCHAR(128), IN p_column VARCHAR(128), IN p_definition TEXT
)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables
               WHERE table_schema=DATABASE() AND table_name=p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.columns
                       WHERE table_schema=DATABASE() AND table_name=p_table AND column_name=p_column) THEN
        SET @sql = CONCAT('ALTER TABLE `', REPLACE(p_table,'`','``'),
                          '` ADD COLUMN `', REPLACE(p_column,'`','``'), '` ', p_definition);
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END$$

DROP PROCEDURE IF EXISTS pd_add_index_if_missing$$
CREATE PROCEDURE pd_add_index_if_missing(
    IN p_table VARCHAR(128), IN p_index VARCHAR(128), IN p_columns TEXT
)
BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.tables
               WHERE table_schema=DATABASE() AND table_name=p_table)
       AND NOT EXISTS (SELECT 1 FROM information_schema.statistics
                       WHERE table_schema=DATABASE() AND table_name=p_table AND index_name=p_index) THEN
        SET @sql = CONCAT('ALTER TABLE `', REPLACE(p_table,'`','``'),
                          '` ADD INDEX `', REPLACE(p_index,'`','``'), '` (', p_columns, ')');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

/* A. Complete authoritative master values */
CALL pd_add_column_if_missing('pump_operator_payments','gross_amount','DECIMAL(20,4) NULL AFTER `payment_amount`');
CALL pd_add_column_if_missing('pump_operator_payments','discount_amount','DECIMAL(20,4) NULL AFTER `gross_amount`');
CALL pd_add_column_if_missing('pump_operator_payments','net_amount','DECIMAL(20,4) NULL AFTER `discount_amount`');
CALL pd_add_column_if_missing('pump_operator_payments','source_type','VARCHAR(50) NULL AFTER `net_amount`');
CALL pd_add_column_if_missing('pump_operator_payments','source_id','BIGINT UNSIGNED NULL AFTER `source_type`');
CALL pd_add_column_if_missing('pump_operator_payments','customer_id','BIGINT UNSIGNED NULL AFTER `source_id`');
CALL pd_add_column_if_missing('pump_operator_payments','transaction_date','DATE NULL AFTER `customer_id`');
CALL pd_add_column_if_missing('pump_operator_payments','reference_no','VARCHAR(191) NULL AFTER `transaction_date`');
CALL pd_add_index_if_missing('pump_operator_payments','pop_business_operator_shift_type_idx','`business_id`,`pump_operator_id`,`shift_id`,`payment_type`');
CALL pd_add_index_if_missing('pump_operator_payments','pop_source_type_id_idx','`source_type`,`source_id`');

CALL pd_add_column_if_missing('settlement_cash_payments','pump_payment_id','BIGINT UNSIGNED NULL AFTER `id`');
CALL pd_add_column_if_missing('settlement_cash_payments','shift_id','BIGINT UNSIGNED NULL AFTER `pump_payment_id`');
CALL pd_add_column_if_missing('settlement_cash_payments','pump_operator_id','BIGINT UNSIGNED NULL AFTER `shift_id`');
CALL pd_add_index_if_missing('settlement_cash_payments','settlement_cash_payments_pump_payment_idx','`pump_payment_id`');
CALL pd_add_index_if_missing('settlement_cash_payments','settlement_cash_payments_business_shift_idx','`business_id`,`shift_id`');
CALL pd_add_index_if_missing('settlement_cash_payments','settlement_cash_payments_business_operator_shift_idx','`business_id`,`pump_operator_id`,`shift_id`');
CALL pd_add_column_if_missing('settlement_card_payments','pump_payment_id','BIGINT UNSIGNED NULL AFTER `id`');
CALL pd_add_column_if_missing('settlement_card_payments','shift_id','BIGINT UNSIGNED NULL AFTER `pump_payment_id`');
CALL pd_add_column_if_missing('settlement_card_payments','pump_operator_id','BIGINT UNSIGNED NULL AFTER `shift_id`');
CALL pd_add_index_if_missing('settlement_card_payments','settlement_card_payments_pump_payment_idx','`pump_payment_id`');
CALL pd_add_index_if_missing('settlement_card_payments','settlement_card_payments_business_shift_idx','`business_id`,`shift_id`');
CALL pd_add_index_if_missing('settlement_card_payments','settlement_card_payments_business_operator_shift_idx','`business_id`,`pump_operator_id`,`shift_id`');
CALL pd_add_column_if_missing('settlement_cheque_payments','pump_payment_id','BIGINT UNSIGNED NULL AFTER `id`');
CALL pd_add_column_if_missing('settlement_cheque_payments','shift_id','BIGINT UNSIGNED NULL AFTER `pump_payment_id`');
CALL pd_add_column_if_missing('settlement_cheque_payments','pump_operator_id','BIGINT UNSIGNED NULL AFTER `shift_id`');
CALL pd_add_index_if_missing('settlement_cheque_payments','settlement_cheque_payments_pump_payment_idx','`pump_payment_id`');
CALL pd_add_index_if_missing('settlement_cheque_payments','settlement_cheque_payments_business_shift_idx','`business_id`,`shift_id`');
CALL pd_add_index_if_missing('settlement_cheque_payments','settlement_cheque_payments_business_operator_shift_idx','`business_id`,`pump_operator_id`,`shift_id`');
CALL pd_add_column_if_missing('settlement_credit_sale_payments','pump_payment_id','BIGINT UNSIGNED NULL AFTER `id`');
CALL pd_add_column_if_missing('settlement_credit_sale_payments','shift_id','BIGINT UNSIGNED NULL AFTER `pump_payment_id`');
CALL pd_add_column_if_missing('settlement_credit_sale_payments','pump_operator_id','BIGINT UNSIGNED NULL AFTER `shift_id`');
CALL pd_add_index_if_missing('settlement_credit_sale_payments','settlement_credit_sale_payments_pump_payment_idx','`pump_payment_id`');
CALL pd_add_index_if_missing('settlement_credit_sale_payments','settlement_credit_sale_payments_business_shift_idx','`business_id`,`shift_id`');
CALL pd_add_index_if_missing('settlement_credit_sale_payments','settlement_credit_sale_payments_business_operator_shift_idx','`business_id`,`pump_operator_id`,`shift_id`');
CALL pd_add_column_if_missing('settlement_shortage_payments','pump_payment_id','BIGINT UNSIGNED NULL AFTER `id`');
CALL pd_add_column_if_missing('settlement_shortage_payments','shift_id','BIGINT UNSIGNED NULL AFTER `pump_payment_id`');
CALL pd_add_column_if_missing('settlement_shortage_payments','pump_operator_id','BIGINT UNSIGNED NULL AFTER `shift_id`');
CALL pd_add_index_if_missing('settlement_shortage_payments','settlement_shortage_payments_pump_payment_idx','`pump_payment_id`');
CALL pd_add_index_if_missing('settlement_shortage_payments','settlement_shortage_payments_business_shift_idx','`business_id`,`shift_id`');
CALL pd_add_index_if_missing('settlement_shortage_payments','settlement_shortage_payments_business_operator_shift_idx','`business_id`,`pump_operator_id`,`shift_id`');
CALL pd_add_column_if_missing('settlement_excess_payments','pump_payment_id','BIGINT UNSIGNED NULL AFTER `id`');
CALL pd_add_column_if_missing('settlement_excess_payments','shift_id','BIGINT UNSIGNED NULL AFTER `pump_payment_id`');
CALL pd_add_column_if_missing('settlement_excess_payments','pump_operator_id','BIGINT UNSIGNED NULL AFTER `shift_id`');
CALL pd_add_index_if_missing('settlement_excess_payments','settlement_excess_payments_pump_payment_idx','`pump_payment_id`');
CALL pd_add_index_if_missing('settlement_excess_payments','settlement_excess_payments_business_shift_idx','`business_id`,`shift_id`');
CALL pd_add_index_if_missing('settlement_excess_payments','settlement_excess_payments_business_operator_shift_idx','`business_id`,`pump_operator_id`,`shift_id`');
CALL pd_add_column_if_missing('daily_collections','pump_payment_id','BIGINT UNSIGNED NULL AFTER `id`');
CALL pd_add_column_if_missing('daily_collections','shift_id','BIGINT UNSIGNED NULL AFTER `pump_payment_id`');
CALL pd_add_column_if_missing('daily_collections','pump_operator_id','BIGINT UNSIGNED NULL AFTER `shift_id`');
CALL pd_add_index_if_missing('daily_collections','daily_collections_pump_payment_idx','`pump_payment_id`');
CALL pd_add_index_if_missing('daily_collections','daily_collections_business_shift_idx','`business_id`,`shift_id`');
CALL pd_add_index_if_missing('daily_collections','daily_collections_business_operator_shift_idx','`business_id`,`pump_operator_id`,`shift_id`');
CALL pd_add_column_if_missing('daily_cards','pump_payment_id','BIGINT UNSIGNED NULL AFTER `id`');
CALL pd_add_column_if_missing('daily_cards','shift_id','BIGINT UNSIGNED NULL AFTER `pump_payment_id`');
CALL pd_add_column_if_missing('daily_cards','pump_operator_id','BIGINT UNSIGNED NULL AFTER `shift_id`');
CALL pd_add_index_if_missing('daily_cards','daily_cards_pump_payment_idx','`pump_payment_id`');
CALL pd_add_index_if_missing('daily_cards','daily_cards_business_shift_idx','`business_id`,`shift_id`');
CALL pd_add_index_if_missing('daily_cards','daily_cards_business_operator_shift_idx','`business_id`,`pump_operator_id`,`shift_id`');
CALL pd_add_column_if_missing('daily_cheque_payments','pump_payment_id','BIGINT UNSIGNED NULL AFTER `id`');
CALL pd_add_column_if_missing('daily_cheque_payments','shift_id','BIGINT UNSIGNED NULL AFTER `pump_payment_id`');
CALL pd_add_column_if_missing('daily_cheque_payments','pump_operator_id','BIGINT UNSIGNED NULL AFTER `shift_id`');
CALL pd_add_index_if_missing('daily_cheque_payments','daily_cheque_payments_pump_payment_idx','`pump_payment_id`');
CALL pd_add_index_if_missing('daily_cheque_payments','daily_cheque_payments_business_shift_idx','`business_id`,`shift_id`');
CALL pd_add_index_if_missing('daily_cheque_payments','daily_cheque_payments_business_operator_shift_idx','`business_id`,`pump_operator_id`,`shift_id`');
CALL pd_add_column_if_missing('daily_vouchers','pump_payment_id','BIGINT UNSIGNED NULL AFTER `id`');
CALL pd_add_column_if_missing('daily_vouchers','shift_id','BIGINT UNSIGNED NULL AFTER `pump_payment_id`');
CALL pd_add_column_if_missing('daily_vouchers','operator_id','BIGINT UNSIGNED NULL AFTER `shift_id`');
CALL pd_add_index_if_missing('daily_vouchers','daily_vouchers_pump_payment_idx','`pump_payment_id`');
CALL pd_add_index_if_missing('daily_vouchers','daily_vouchers_business_shift_idx','`business_id`,`shift_id`');
CALL pd_add_index_if_missing('daily_vouchers','daily_vouchers_business_operator_shift_idx','`business_id`,`operator_id`,`shift_id`');


/* B. Reconciliation event log */
CREATE TABLE IF NOT EXISTS petro_pd_payment_reconciliation_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id BIGINT UNSIGNED NOT NULL,
    pump_operator_id BIGINT UNSIGNED NULL,
    shift_ids VARCHAR(500) NULL,
    settlement_id BIGINT UNSIGNED NULL,
    settlement_no VARCHAR(191) NULL,
    pump_payment_id BIGINT UNSIGNED NULL,
    issue_type VARCHAR(100) NOT NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'critical',
    message TEXT NOT NULL,
    context_json LONGTEXT NULL,
    snapshot_fingerprint VARCHAR(64) NULL,
    event_hash VARCHAR(64) NOT NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY ppdre_event_hash_uq (event_hash),
    KEY ppdre_business_idx (business_id),
    KEY ppdre_operator_idx (pump_operator_id),
    KEY ppdre_settlement_id_idx (settlement_id),
    KEY ppdre_settlement_no_idx (settlement_no),
    KEY ppdre_payment_idx (pump_payment_id),
    KEY ppdre_issue_idx (issue_type),
    KEY ppdre_severity_idx (severity),
    KEY ppdre_resolved_idx (resolved_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* C. Fill complete master values without changing legacy payment_amount */
UPDATE pump_operator_payments
SET gross_amount = COALESCE(gross_amount, payment_amount),
    discount_amount = COALESCE(discount_amount, 0),
    net_amount = COALESCE(net_amount, payment_amount),
    transaction_date = COALESCE(transaction_date, DATE(date_and_time), DATE(created_at)),
    source_type = COALESCE(NULLIF(source_type,''), CONCAT('pumper_dashboard_', LOWER(payment_type)))
WHERE LOWER(payment_type) NOT IN ('credit','multiple_credit');

/* Credit links: exact business/operator/Shift/collection and one master candidate only */
UPDATE settlement_credit_sale_payments scsp
JOIN (
    SELECT business_id,pump_operator_id,shift_id,collection_form_no,
           MIN(id) AS pump_payment_id,COUNT(*) AS cnt
    FROM pump_operator_payments
    WHERE LOWER(payment_type) IN ('credit','multiple_credit')
      AND shift_id IS NOT NULL AND shift_id>0
      AND collection_form_no IS NOT NULL AND collection_form_no<>''
    GROUP BY business_id,pump_operator_id,shift_id,collection_form_no
    HAVING COUNT(*)=1
) pop ON pop.business_id=scsp.business_id
     AND pop.pump_operator_id=scsp.pump_operator_id
     AND pop.shift_id=scsp.shift_id
     AND CAST(pop.collection_form_no AS CHAR)=CAST(scsp.collection_form_no AS CHAR)
SET scsp.pump_payment_id=pop.pump_payment_id
WHERE scsp.pump_payment_id IS NULL OR scsp.pump_payment_id=0;

UPDATE settlement_credit_sale_payments scsp
JOIN pump_operator_payments pop ON pop.id=scsp.pump_payment_id
SET scsp.shift_id=pop.shift_id
WHERE scsp.pump_payment_id IS NOT NULL AND scsp.pump_payment_id>0
  AND (scsp.shift_id IS NULL OR scsp.shift_id=0);

UPDATE pump_operator_payments pop
LEFT JOIN settlement_credit_sale_payments scsp ON scsp.pump_payment_id=pop.id
SET pop.gross_amount=COALESCE(pop.gross_amount,pop.payment_amount),
    pop.discount_amount=COALESCE(pop.discount_amount,scsp.total_discount,0),
    pop.net_amount=COALESCE(pop.net_amount,scsp.sub_total,pop.payment_amount-COALESCE(scsp.total_discount,0)),
    pop.customer_id=COALESCE(pop.customer_id,scsp.customer_id),
    pop.transaction_date=COALESCE(pop.transaction_date,scsp.order_date,DATE(pop.date_and_time),DATE(pop.created_at)),
    pop.reference_no=COALESCE(NULLIF(pop.reference_no,''),NULLIF(scsp.bill_number,''),NULLIF(scsp.order_number,'')),
    pop.source_type=COALESCE(NULLIF(pop.source_type,''),'credit_sale'),
    pop.source_id=COALESCE(pop.source_id,scsp.id)
WHERE LOWER(pop.payment_type) IN ('credit','multiple_credit');

/* D. Strong/direct operational links */
UPDATE daily_cheque_payments d
JOIN pump_operator_payments p ON p.id=d.linked_payment_id AND p.business_id=d.business_id
SET d.pump_payment_id=p.id,d.shift_id=p.shift_id
WHERE (d.pump_payment_id IS NULL OR d.pump_payment_id=0)
  AND LOWER(p.payment_type) IN ('cheque','cheques');


UPDATE `daily_collections` d
JOIN (
    SELECT business_id,pump_operator_id,collection_form_no,
           MIN(id) AS pump_payment_id,COUNT(*) AS cnt
    FROM pump_operator_payments
    WHERE LOWER(payment_type) IN ('cash')
      AND collection_form_no IS NOT NULL AND collection_form_no<>''
    GROUP BY business_id,pump_operator_id,collection_form_no
    HAVING COUNT(*)=1
) p ON p.business_id=d.business_id
   AND p.pump_operator_id=d.`pump_operator_id`
   AND CAST(p.collection_form_no AS CHAR)=CAST(d.`collection_form_no` AS CHAR)
SET d.pump_payment_id=p.pump_payment_id
WHERE d.pump_payment_id IS NULL OR d.pump_payment_id=0;

UPDATE `daily_collections` d
JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=p.shift_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0
  AND p.shift_id IS NOT NULL AND p.shift_id>0
  AND (d.shift_id IS NULL OR d.shift_id=0);


UPDATE `daily_cards` d
JOIN (
    SELECT business_id,pump_operator_id,collection_form_no,
           MIN(id) AS pump_payment_id,COUNT(*) AS cnt
    FROM pump_operator_payments
    WHERE LOWER(payment_type) IN ('card','cards')
      AND collection_form_no IS NOT NULL AND collection_form_no<>''
    GROUP BY business_id,pump_operator_id,collection_form_no
    HAVING COUNT(*)=1
) p ON p.business_id=d.business_id
   AND p.pump_operator_id=d.`pump_operator_id`
   AND CAST(p.collection_form_no AS CHAR)=CAST(d.`collection_no` AS CHAR)
SET d.pump_payment_id=p.pump_payment_id
WHERE d.pump_payment_id IS NULL OR d.pump_payment_id=0;

UPDATE `daily_cards` d
JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=p.shift_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0
  AND p.shift_id IS NOT NULL AND p.shift_id>0
  AND (d.shift_id IS NULL OR d.shift_id=0);


UPDATE `daily_cheque_payments` d
JOIN (
    SELECT business_id,pump_operator_id,collection_form_no,
           MIN(id) AS pump_payment_id,COUNT(*) AS cnt
    FROM pump_operator_payments
    WHERE LOWER(payment_type) IN ('cheque','cheques')
      AND collection_form_no IS NOT NULL AND collection_form_no<>''
    GROUP BY business_id,pump_operator_id,collection_form_no
    HAVING COUNT(*)=1
) p ON p.business_id=d.business_id
   AND p.pump_operator_id=d.`pump_operator_id`
   AND CAST(p.collection_form_no AS CHAR)=CAST(d.`collection_form_no` AS CHAR)
SET d.pump_payment_id=p.pump_payment_id
WHERE d.pump_payment_id IS NULL OR d.pump_payment_id=0;

UPDATE `daily_cheque_payments` d
JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=p.shift_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0
  AND p.shift_id IS NOT NULL AND p.shift_id>0
  AND (d.shift_id IS NULL OR d.shift_id=0);


UPDATE `daily_vouchers` d
JOIN (
    SELECT business_id,pump_operator_id,collection_form_no,
           MIN(id) AS pump_payment_id,COUNT(*) AS cnt
    FROM pump_operator_payments
    WHERE LOWER(payment_type) IN ('credit','multiple_credit')
      AND collection_form_no IS NOT NULL AND collection_form_no<>''
    GROUP BY business_id,pump_operator_id,collection_form_no
    HAVING COUNT(*)=1
) p ON p.business_id=d.business_id
   AND p.pump_operator_id=d.`operator_id`
   AND CAST(p.collection_form_no AS CHAR)=CAST(d.`daily_vouchers_no` AS CHAR)
SET d.pump_payment_id=p.pump_payment_id
WHERE d.pump_payment_id IS NULL OR d.pump_payment_id=0;

UPDATE `daily_vouchers` d
JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=p.shift_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0
  AND p.shift_id IS NOT NULL AND p.shift_id>0
  AND (d.shift_id IS NULL OR d.shift_id=0);


UPDATE `settlement_cash_payments` d
JOIN pump_operator_payments p
  ON p.business_id=d.business_id AND p.parent_id=d.id
 AND LOWER(p.payment_type) IN ('cash')
SET d.pump_payment_id=p.id,d.shift_id=p.shift_id
WHERE (d.pump_payment_id IS NULL OR d.pump_payment_id=0)
  AND p.shift_id IS NOT NULL AND p.shift_id>0;

UPDATE `settlement_cash_payments` d
JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=p.shift_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0
  AND p.shift_id IS NOT NULL AND p.shift_id>0
  AND (d.shift_id IS NULL OR d.shift_id=0);


UPDATE `settlement_card_payments` d
JOIN pump_operator_payments p
  ON p.business_id=d.business_id AND p.parent_id=d.id
 AND LOWER(p.payment_type) IN ('card','cards')
SET d.pump_payment_id=p.id,d.shift_id=p.shift_id
WHERE (d.pump_payment_id IS NULL OR d.pump_payment_id=0)
  AND p.shift_id IS NOT NULL AND p.shift_id>0;

UPDATE `settlement_card_payments` d
JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=p.shift_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0
  AND p.shift_id IS NOT NULL AND p.shift_id>0
  AND (d.shift_id IS NULL OR d.shift_id=0);


UPDATE `settlement_cheque_payments` d
JOIN pump_operator_payments p
  ON p.business_id=d.business_id AND p.parent_id=d.id
 AND LOWER(p.payment_type) IN ('cheque','cheques')
SET d.pump_payment_id=p.id,d.shift_id=p.shift_id
WHERE (d.pump_payment_id IS NULL OR d.pump_payment_id=0)
  AND p.shift_id IS NOT NULL AND p.shift_id>0;

UPDATE `settlement_cheque_payments` d
JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=p.shift_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0
  AND p.shift_id IS NOT NULL AND p.shift_id>0
  AND (d.shift_id IS NULL OR d.shift_id=0);


UPDATE `settlement_credit_sale_payments` d
JOIN pump_operator_payments p
  ON p.business_id=d.business_id AND p.parent_id=d.id
 AND LOWER(p.payment_type) IN ('credit','multiple_credit')
SET d.pump_payment_id=p.id,d.shift_id=p.shift_id
WHERE (d.pump_payment_id IS NULL OR d.pump_payment_id=0)
  AND p.shift_id IS NOT NULL AND p.shift_id>0;

UPDATE `settlement_credit_sale_payments` d
JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=p.shift_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0
  AND p.shift_id IS NOT NULL AND p.shift_id>0
  AND (d.shift_id IS NULL OR d.shift_id=0);


UPDATE `settlement_shortage_payments` d
JOIN pump_operator_payments p
  ON p.business_id=d.business_id AND p.parent_id=d.id
 AND LOWER(p.payment_type) IN ('shortage')
SET d.pump_payment_id=p.id,d.shift_id=p.shift_id
WHERE (d.pump_payment_id IS NULL OR d.pump_payment_id=0)
  AND p.shift_id IS NOT NULL AND p.shift_id>0;

UPDATE `settlement_shortage_payments` d
JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=p.shift_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0
  AND p.shift_id IS NOT NULL AND p.shift_id>0
  AND (d.shift_id IS NULL OR d.shift_id=0);


UPDATE `settlement_excess_payments` d
JOIN pump_operator_payments p
  ON p.business_id=d.business_id AND p.parent_id=d.id
 AND LOWER(p.payment_type) IN ('excess')
SET d.pump_payment_id=p.id,d.shift_id=p.shift_id
WHERE (d.pump_payment_id IS NULL OR d.pump_payment_id=0)
  AND p.shift_id IS NOT NULL AND p.shift_id>0;

UPDATE `settlement_excess_payments` d
JOIN pump_operator_payments p ON p.id=d.pump_payment_id
SET d.shift_id=p.shift_id
WHERE d.pump_payment_id IS NOT NULL AND d.pump_payment_id>0
  AND p.shift_id IS NOT NULL AND p.shift_id>0
  AND (d.shift_id IS NULL OR d.shift_id=0);


/* E. Database guards */
DELIMITER $$
DROP TRIGGER IF EXISTS trg_pop_authority_bi_20260723$$
CREATE TRIGGER trg_pop_authority_bi_20260723
BEFORE INSERT ON pump_operator_payments
FOR EACH ROW
BEGIN
    IF NEW.pump_operator_id IS NOT NULL AND NEW.pump_operator_id>0
       AND LOWER(NEW.payment_type) IN ('cash','card','cards','cheque','cheques','credit','multiple_credit','other','shortage','excess')
       AND (NEW.shift_id IS NULL OR NEW.shift_id=0) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Pump Operator Payment requires an immutable Shift ID';
    END IF;
    IF NEW.source_type IS NOT NULL AND NEW.source_type<>''
       AND NEW.source_id IS NOT NULL AND NEW.source_id>0
       AND EXISTS (SELECT 1 FROM pump_operator_payments p
                   WHERE p.business_id=NEW.business_id
                     AND p.source_type=NEW.source_type AND p.source_id=NEW.source_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate authoritative Pumper Dashboard payment source';
    END IF;
END$$

DROP TRIGGER IF EXISTS trg_pop_authority_bu_20260723$$
CREATE TRIGGER trg_pop_authority_bu_20260723
BEFORE UPDATE ON pump_operator_payments
FOR EACH ROW
BEGIN
    IF OLD.business_id IS NOT NULL AND OLD.business_id>0 AND NEW.business_id<>OLD.business_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Pump Operator Payment business is immutable';
    END IF;
    IF OLD.pump_operator_id IS NOT NULL AND OLD.pump_operator_id>0
       AND NEW.pump_operator_id<>OLD.pump_operator_id THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Pump Operator Payment operator is immutable';
    END IF;
    IF OLD.shift_id IS NOT NULL AND OLD.shift_id>0 AND NOT (NEW.shift_id <=> OLD.shift_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Pump Operator Payment Shift ID is immutable';
    END IF;
    IF OLD.source_type IS NOT NULL AND OLD.source_type<>''
       AND OLD.source_id IS NOT NULL AND OLD.source_id>0
       AND (NOT (NEW.source_type <=> OLD.source_type) OR NOT (NEW.source_id <=> OLD.source_id)) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Pump Operator Payment source identity is immutable';
    END IF;
    IF NEW.source_type IS NOT NULL AND NEW.source_type<>''
       AND NEW.source_id IS NOT NULL AND NEW.source_id>0
       AND EXISTS (SELECT 1 FROM pump_operator_payments p
                   WHERE p.business_id=NEW.business_id
                     AND p.source_type=NEW.source_type AND p.source_id=NEW.source_id
                     AND p.id<>NEW.id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate authoritative Pumper Dashboard payment source';
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_scp_authority_bi_20260723$$
CREATE TRIGGER trg_scp_authority_bi_20260723
BEFORE INSERT ON `settlement_cash_payments`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `settlement_cash_payments` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_scp_authority_bu_20260723$$
CREATE TRIGGER trg_scp_authority_bu_20260723
BEFORE UPDATE ON `settlement_cash_payments`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF OLD.shift_id IS NOT NULL AND OLD.shift_id>0 AND NOT (NEW.shift_id <=> OLD.shift_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID is immutable';
    END IF;
    IF OLD.pump_payment_id IS NOT NULL AND OLD.pump_payment_id>0
       AND NOT (NEW.pump_payment_id <=> OLD.pump_payment_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail master link is immutable';
    END IF;

    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `settlement_cash_payments` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id AND x.id<>NEW.id) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_scardp_authority_bi_20260723$$
CREATE TRIGGER trg_scardp_authority_bi_20260723
BEFORE INSERT ON `settlement_card_payments`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `settlement_card_payments` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_scardp_authority_bu_20260723$$
CREATE TRIGGER trg_scardp_authority_bu_20260723
BEFORE UPDATE ON `settlement_card_payments`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF OLD.shift_id IS NOT NULL AND OLD.shift_id>0 AND NOT (NEW.shift_id <=> OLD.shift_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID is immutable';
    END IF;
    IF OLD.pump_payment_id IS NOT NULL AND OLD.pump_payment_id>0
       AND NOT (NEW.pump_payment_id <=> OLD.pump_payment_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail master link is immutable';
    END IF;

    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `settlement_card_payments` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id AND x.id<>NEW.id) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_schqp_authority_bi_20260723$$
CREATE TRIGGER trg_schqp_authority_bi_20260723
BEFORE INSERT ON `settlement_cheque_payments`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `settlement_cheque_payments` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_schqp_authority_bu_20260723$$
CREATE TRIGGER trg_schqp_authority_bu_20260723
BEFORE UPDATE ON `settlement_cheque_payments`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF OLD.shift_id IS NOT NULL AND OLD.shift_id>0 AND NOT (NEW.shift_id <=> OLD.shift_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID is immutable';
    END IF;
    IF OLD.pump_payment_id IS NOT NULL AND OLD.pump_payment_id>0
       AND NOT (NEW.pump_payment_id <=> OLD.pump_payment_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail master link is immutable';
    END IF;

    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `settlement_cheque_payments` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id AND x.id<>NEW.id) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_scrp_authority_bi_20260723$$
CREATE TRIGGER trg_scrp_authority_bi_20260723
BEFORE INSERT ON `settlement_credit_sale_payments`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `settlement_credit_sale_payments` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_scrp_authority_bu_20260723$$
CREATE TRIGGER trg_scrp_authority_bu_20260723
BEFORE UPDATE ON `settlement_credit_sale_payments`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF OLD.shift_id IS NOT NULL AND OLD.shift_id>0 AND NOT (NEW.shift_id <=> OLD.shift_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID is immutable';
    END IF;
    IF OLD.pump_payment_id IS NOT NULL AND OLD.pump_payment_id>0
       AND NOT (NEW.pump_payment_id <=> OLD.pump_payment_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail master link is immutable';
    END IF;

    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `settlement_credit_sale_payments` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id AND x.id<>NEW.id) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_sshp_authority_bi_20260723$$
CREATE TRIGGER trg_sshp_authority_bi_20260723
BEFORE INSERT ON `settlement_shortage_payments`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `settlement_shortage_payments` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_sshp_authority_bu_20260723$$
CREATE TRIGGER trg_sshp_authority_bu_20260723
BEFORE UPDATE ON `settlement_shortage_payments`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF OLD.shift_id IS NOT NULL AND OLD.shift_id>0 AND NOT (NEW.shift_id <=> OLD.shift_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID is immutable';
    END IF;
    IF OLD.pump_payment_id IS NOT NULL AND OLD.pump_payment_id>0
       AND NOT (NEW.pump_payment_id <=> OLD.pump_payment_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail master link is immutable';
    END IF;

    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `settlement_shortage_payments` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id AND x.id<>NEW.id) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_sexp_authority_bi_20260723$$
CREATE TRIGGER trg_sexp_authority_bi_20260723
BEFORE INSERT ON `settlement_excess_payments`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `settlement_excess_payments` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_sexp_authority_bu_20260723$$
CREATE TRIGGER trg_sexp_authority_bu_20260723
BEFORE UPDATE ON `settlement_excess_payments`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF OLD.shift_id IS NOT NULL AND OLD.shift_id>0 AND NOT (NEW.shift_id <=> OLD.shift_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID is immutable';
    END IF;
    IF OLD.pump_payment_id IS NOT NULL AND OLD.pump_payment_id>0
       AND NOT (NEW.pump_payment_id <=> OLD.pump_payment_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail master link is immutable';
    END IF;

    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `settlement_excess_payments` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id AND x.id<>NEW.id) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_dcol_authority_bi_20260723$$
CREATE TRIGGER trg_dcol_authority_bi_20260723
BEFORE INSERT ON `daily_collections`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `daily_collections` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_dcol_authority_bu_20260723$$
CREATE TRIGGER trg_dcol_authority_bu_20260723
BEFORE UPDATE ON `daily_collections`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF OLD.shift_id IS NOT NULL AND OLD.shift_id>0 AND NOT (NEW.shift_id <=> OLD.shift_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID is immutable';
    END IF;
    IF OLD.pump_payment_id IS NOT NULL AND OLD.pump_payment_id>0
       AND NOT (NEW.pump_payment_id <=> OLD.pump_payment_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail master link is immutable';
    END IF;

    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `daily_collections` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id AND x.id<>NEW.id) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_dcard_authority_bi_20260723$$
CREATE TRIGGER trg_dcard_authority_bi_20260723
BEFORE INSERT ON `daily_cards`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `daily_cards` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_dcard_authority_bu_20260723$$
CREATE TRIGGER trg_dcard_authority_bu_20260723
BEFORE UPDATE ON `daily_cards`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF OLD.shift_id IS NOT NULL AND OLD.shift_id>0 AND NOT (NEW.shift_id <=> OLD.shift_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID is immutable';
    END IF;
    IF OLD.pump_payment_id IS NOT NULL AND OLD.pump_payment_id>0
       AND NOT (NEW.pump_payment_id <=> OLD.pump_payment_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail master link is immutable';
    END IF;

    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `daily_cards` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id AND x.id<>NEW.id) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_dchq_authority_bi_20260723$$
CREATE TRIGGER trg_dchq_authority_bi_20260723
BEFORE INSERT ON `daily_cheque_payments`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `daily_cheque_payments` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_dchq_authority_bu_20260723$$
CREATE TRIGGER trg_dchq_authority_bu_20260723
BEFORE UPDATE ON `daily_cheque_payments`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF OLD.shift_id IS NOT NULL AND OLD.shift_id>0 AND NOT (NEW.shift_id <=> OLD.shift_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID is immutable';
    END IF;
    IF OLD.pump_payment_id IS NOT NULL AND OLD.pump_payment_id>0
       AND NOT (NEW.pump_payment_id <=> OLD.pump_payment_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail master link is immutable';
    END IF;

    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`pump_operator_id` IS NULL OR NEW.`pump_operator_id`=0 THEN SET NEW.`pump_operator_id`=v_operator;
        ELSEIF NEW.`pump_operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `daily_cheque_payments` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id AND x.id<>NEW.id) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_dvch_authority_bi_20260723$$
CREATE TRIGGER trg_dvch_authority_bi_20260723
BEFORE INSERT ON `daily_vouchers`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`operator_id` IS NULL OR NEW.`operator_id`=0 THEN SET NEW.`operator_id`=v_operator;
        ELSEIF NEW.`operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `daily_vouchers` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id ) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DROP TRIGGER IF EXISTS trg_dvch_authority_bu_20260723$$
CREATE TRIGGER trg_dvch_authority_bu_20260723
BEFORE UPDATE ON `daily_vouchers`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business BIGINT DEFAULT NULL;
    DECLARE v_operator BIGINT DEFAULT NULL;
    DECLARE v_shift BIGINT DEFAULT NULL;
    IF OLD.shift_id IS NOT NULL AND OLD.shift_id>0 AND NOT (NEW.shift_id <=> OLD.shift_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID is immutable';
    END IF;
    IF OLD.pump_payment_id IS NOT NULL AND OLD.pump_payment_id>0
       AND NOT (NEW.pump_payment_id <=> OLD.pump_payment_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail master link is immutable';
    END IF;

    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id>0 THEN
        SELECT COUNT(*),MAX(business_id),MAX(pump_operator_id),MAX(shift_id)
          INTO v_found,v_business,v_operator,v_shift
        FROM pump_operator_payments WHERE id=NEW.pump_payment_id;
        IF v_found<>1 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid authoritative Pump Operator Payment link';
        END IF;
        IF NEW.business_id IS NULL OR NEW.business_id=0 THEN SET NEW.business_id=v_business;
        ELSEIF NEW.business_id<>v_business THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail business does not match master';
        END IF;
        IF NEW.`operator_id` IS NULL OR NEW.`operator_id`=0 THEN SET NEW.`operator_id`=v_operator;
        ELSEIF NEW.`operator_id`<>v_operator THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail operator does not match master';
        END IF;
        IF NEW.shift_id IS NULL OR NEW.shift_id=0 THEN SET NEW.shift_id=v_shift;
        ELSEIF NEW.shift_id<>v_shift THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payment detail Shift ID does not match master';
        END IF;
        IF EXISTS (SELECT 1 FROM `daily_vouchers` x
                   WHERE x.business_id=NEW.business_id
                     AND x.pump_payment_id=NEW.pump_payment_id AND x.id<>NEW.id) THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate detail for Pump Operator Payment';
        END IF;
    END IF;
END$$


DELIMITER ;

DROP PROCEDURE IF EXISTS pd_add_column_if_missing;
DROP PROCEDURE IF EXISTS pd_add_index_if_missing;
SET SQL_SAFE_UPDATES = @OLD_SQL_SAFE_UPDATES;

/* Run PETROPD_PAYMENT_INTEGRITY_AUDIT_20260723.sql next. */


/* ===== PARCEL 2 CONTROL CENTRE EXTENSION ===== */

/*
 PETROPD PAYMENT RECONCILIATION CONTROL CENTRE - PARCEL 2
 23 July 2026

 Run on EACH tenant database AFTER Parcel 1 payment-integrity SQL/migrations.
 Idempotent. No financial amount or Shift ID is changed.
*/

DELIMITER $$
DROP PROCEDURE IF EXISTS pd_add_recon_column_if_missing$$
CREATE PROCEDURE pd_add_recon_column_if_missing(
    IN p_column VARCHAR(128), IN p_definition TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'petro_pd_payment_reconciliation_events'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'petro_pd_payment_reconciliation_events'
          AND column_name = p_column
    ) THEN
        SET @sql = CONCAT(
            'ALTER TABLE `petro_pd_payment_reconciliation_events` ADD COLUMN `',
            REPLACE(p_column, '`', '``'), '` ', p_definition
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DROP PROCEDURE IF EXISTS pd_add_recon_index_if_missing$$
CREATE PROCEDURE pd_add_recon_index_if_missing(
    IN p_index VARCHAR(128), IN p_columns TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = 'petro_pd_payment_reconciliation_events'
    ) AND NOT EXISTS (
        SELECT 1 FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = 'petro_pd_payment_reconciliation_events'
          AND index_name = p_index
    ) THEN
        SET @sql = CONCAT(
            'ALTER TABLE `petro_pd_payment_reconciliation_events` ADD INDEX `',
            REPLACE(p_index, '`', '``'), '` (', p_columns, ')'
        );
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL pd_add_recon_column_if_missing('first_seen_at', 'TIMESTAMP NULL AFTER `event_hash`');
CALL pd_add_recon_column_if_missing('last_seen_at', 'TIMESTAMP NULL AFTER `first_seen_at`');
CALL pd_add_recon_column_if_missing('last_checked_at', 'TIMESTAMP NULL AFTER `last_seen_at`');
CALL pd_add_recon_column_if_missing('occurrence_count', 'INT UNSIGNED NOT NULL DEFAULT 1 AFTER `last_checked_at`');
CALL pd_add_recon_column_if_missing('resolved_by', 'BIGINT UNSIGNED NULL AFTER `resolved_at`');
CALL pd_add_recon_column_if_missing('resolution_note', 'TEXT NULL AFTER `resolved_by`');

CALL pd_add_recon_index_if_missing('ppdre_first_seen_idx', '`first_seen_at`');
CALL pd_add_recon_index_if_missing('ppdre_last_seen_idx', '`last_seen_at`');
CALL pd_add_recon_index_if_missing('ppdre_last_checked_idx', '`last_checked_at`');
CALL pd_add_recon_index_if_missing('ppdre_resolved_by_idx', '`resolved_by`');
CALL pd_add_recon_index_if_missing('ppdre_business_open_severity_idx', '`business_id`,`resolved_at`,`severity`');
CALL pd_add_recon_index_if_missing('ppdre_business_operator_shift_idx', '`business_id`,`pump_operator_id`,`settlement_id`');

UPDATE petro_pd_payment_reconciliation_events
SET first_seen_at = COALESCE(first_seen_at, created_at, NOW()),
    last_seen_at = COALESCE(last_seen_at, updated_at, created_at, NOW()),
    occurrence_count = CASE WHEN occurrence_count IS NULL OR occurrence_count = 0 THEN 1 ELSE occurrence_count END;

DROP PROCEDURE IF EXISTS pd_add_recon_column_if_missing;
DROP PROCEDURE IF EXISTS pd_add_recon_index_if_missing;

/* Verification */
SELECT
    COUNT(*) AS total_events,
    SUM(CASE WHEN resolved_at IS NULL AND LOWER(COALESCE(severity,'critical'))='critical' THEN 1 ELSE 0 END) AS open_critical,
    SUM(CASE WHEN resolved_at IS NOT NULL THEN 1 ELSE 0 END) AS resolved_events,
    MAX(last_seen_at) AS latest_seen_at
FROM petro_pd_payment_reconciliation_events;
