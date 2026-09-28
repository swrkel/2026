-- Petro PD-New / Pumper Dashboard-New performance indexes
-- Idempotent: safe to run repeatedly in every tenant database.
-- Date: 03 Aug 2026

DROP PROCEDURE IF EXISTS `pdnew_add_performance_index_if_missing`;
DELIMITER $$
CREATE PROCEDURE `pdnew_add_performance_index_if_missing`(
    IN p_table_name VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN p_index_columns TEXT
)
BEGIN
    DECLARE v_table_count INT DEFAULT 0;
    DECLARE v_index_count INT DEFAULT 0;

    SELECT COUNT(*) INTO v_table_count
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = p_table_name;

    SELECT COUNT(*) INTO v_index_count
    FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = p_table_name
      AND index_name = p_index_name;

    IF v_table_count > 0 AND v_index_count = 0 THEN
        SET @pdnew_index_sql = CONCAT(
            'ALTER TABLE `', REPLACE(p_table_name, '`', '``'),
            '` ADD INDEX `', REPLACE(p_index_name, '`', '``'),
            '` (', p_index_columns, ')'
        );
        PREPARE pdnew_index_stmt FROM @pdnew_index_sql;
        EXECUTE pdnew_index_stmt;
        DEALLOCATE PREPARE pdnew_index_stmt;
    END IF;
END$$
DELIMITER ;

-- Petro PD-New primary pages
CALL `pdnew_add_performance_index_if_missing`('pdnew_settlements', 'pdnew_settle_scope_status_id_ix', '`business_id`,`location_id`,`status`,`id`');
CALL `pdnew_add_performance_index_if_missing`('pdnew_source_imports', 'pdnew_source_scope_closed_ix', '`business_id`,`location_id`,`import_status`,`source_closed_at`');
CALL `pdnew_add_performance_index_if_missing`('pdnew_operator_mappings', 'pdnew_operator_scope_profile_ix', '`business_id`,`location_id`,`pone_operator_profile_id`,`status`');
CALL `pdnew_add_performance_index_if_missing`('pdnew_reconciliation_issues', 'pdnew_issue_scope_status_ix', '`business_id`,`status`,`settlement_id`');
CALL `pdnew_add_performance_index_if_missing`('pdnew_integration_outbox', 'pdnew_outbox_scope_event_ix', '`business_id`,`status`,`event_type`,`id`');
CALL `pdnew_add_performance_index_if_missing`('pdnew_integration_logs', 'pdnew_log_scope_date_ix', '`business_id`,`status`,`created_at`');
CALL `pdnew_add_performance_index_if_missing`('pdnew_audit_logs', 'pdnew_audit_scope_user_date_ix', '`business_id`,`location_id`,`user_id`,`created_at`');
CALL `pdnew_add_performance_index_if_missing`('pdnew_day_ends', 'pdnew_dayend_scope_status_date_ix', '`business_id`,`location_id`,`status`,`day_end_date`');

-- Pumper Dashboard-New source tables used by every PD Operators tab
CALL `pdnew_add_performance_index_if_missing`('pone_shifts', 'pone_shift_workspace_ix', '`business_id`,`location_id`,`operator_profile_id`,`status`,`opened_at`');
CALL `pdnew_add_performance_index_if_missing`('pone_pump_assignments', 'pone_assignment_workspace_ix', '`business_id`,`location_id`,`operator_profile_id`,`status`,`assigned_at`');
CALL `pdnew_add_performance_index_if_missing`('pone_pump_assignments', 'pone_assignment_pump_status_ix', '`business_id`,`location_id`,`pump_id`,`status`');
CALL `pdnew_add_performance_index_if_missing`('pone_payments', 'pone_payment_workspace_ix', '`business_id`,`location_id`,`operator_profile_id`,`payment_type`,`status`,`transaction_at`');
CALL `pdnew_add_performance_index_if_missing`('pone_day_entries', 'pone_dayentry_workspace_ix', '`business_id`,`location_id`,`operator_profile_id`,`status`,`entry_at`');
CALL `pdnew_add_performance_index_if_missing`('pone_meter_readings', 'pone_meter_workspace_ix', '`business_id`,`location_id`,`operator_profile_id`,`reading_type`,`recorded_at`');
CALL `pdnew_add_performance_index_if_missing`('pone_unload_stocks', 'pone_unload_workspace_ix', '`business_id`,`location_id`,`operator_profile_id`,`status`,`unloaded_at`');
CALL `pdnew_add_performance_index_if_missing`('pone_shortage_recoveries', 'pone_shortage_workspace_ix', '`business_id`,`location_id`,`operator_profile_id`,`status`,`recovery_date`');
CALL `pdnew_add_performance_index_if_missing`('pone_excess_commissions', 'pone_commission_workspace_ix', '`business_id`,`location_id`,`operator_profile_id`,`status`,`commission_date`');
CALL `pdnew_add_performance_index_if_missing`('pone_operator_ledger_entries', 'pone_ledger_workspace_ix', '`business_id`,`location_id`,`operator_profile_id`,`status`,`entry_at`');
CALL `pdnew_add_performance_index_if_missing`('pone_daily_collections', 'pone_collection_workspace_ix', '`business_id`,`location_id`,`operator_profile_id`,`status`,`collection_at`');
CALL `pdnew_add_performance_index_if_missing`('pone_shift_settlement_references', 'pone_setref_scope_shift_ix', '`business_id`,`shift_id`,`settlement_date`');

DROP PROCEDURE IF EXISTS `pdnew_add_performance_index_if_missing`;

SELECT 'Petro PD-New performance indexes checked successfully.' AS result;
