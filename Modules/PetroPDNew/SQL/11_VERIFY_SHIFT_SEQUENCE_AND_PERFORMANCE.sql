-- Verify Petro PD-New automatic shift numbering and performance indexes.

SELECT
    'pone_number_sequences business-wide shift sequence' AS object_name,
    CASE WHEN EXISTS (
        SELECT 1 FROM `pone_number_sequences`
        WHERE `sequence_type` = 'shift' AND `location_id` IS NULL
    ) THEN 'OK' ELSE 'NOT INITIALIZED - save Petro PD-New Settings once' END AS status;

SELECT
    `business_id`, `scope_key`, `prefix`, `next_number`, `padding`, `updated_at`
FROM `pone_number_sequences`
WHERE `sequence_type` = 'shift'
ORDER BY `business_id`, `location_id` IS NOT NULL, `location_id`;

SELECT
    `table_name`, `index_name`,
    GROUP_CONCAT(`column_name` ORDER BY `seq_in_index`) AS indexed_columns
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND index_name IN (
    'pdnew_settle_scope_status_id_ix','pdnew_source_scope_closed_ix',
    'pdnew_operator_scope_profile_ix','pdnew_issue_scope_status_ix',
    'pdnew_outbox_scope_event_ix','pdnew_log_scope_date_ix',
    'pdnew_audit_scope_user_date_ix','pdnew_dayend_scope_status_date_ix',
    'pone_shift_workspace_ix','pone_assignment_workspace_ix',
    'pone_assignment_pump_status_ix','pone_payment_workspace_ix',
    'pone_dayentry_workspace_ix','pone_meter_workspace_ix',
    'pone_unload_workspace_ix','pone_shortage_workspace_ix',
    'pone_commission_workspace_ix','pone_ledger_workspace_ix',
    'pone_collection_workspace_ix','pone_setref_scope_shift_ix'
  )
GROUP BY `table_name`, `index_name`
ORDER BY `table_name`, `index_name`;
