-- Pumper Dashboard-New complete permission installer (36 permissions).
-- Safe to execute repeatedly.
SET NAMES utf8mb4;

-- Idempotent permission registration. Skipped when the host permissions table is absent.
SET @pone_permission_sql = IF(
  EXISTS(
    SELECT 1 FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'permissions'
  ),
  'INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
   SELECT p.name, ''web'', NOW(), NOW()
   FROM (
     SELECT ''pumper_dashboard_new.access'' AS name
     UNION ALL SELECT ''pumper_dashboard_new.dashboard.view''
     UNION ALL SELECT ''pumper_dashboard_new.operators.view''
     UNION ALL SELECT ''pumper_dashboard_new.operators.manage''
     UNION ALL SELECT ''pumper_dashboard_new.shifts.view''
     UNION ALL SELECT ''pumper_dashboard_new.shifts.manage''
     UNION ALL SELECT ''pumper_dashboard_new.assignments.manage''
     UNION ALL SELECT ''pumper_dashboard_new.collections.view''
     UNION ALL SELECT ''pumper_dashboard_new.collections.manage''
     UNION ALL SELECT ''pumper_dashboard_new.reconciliation.view''
     UNION ALL SELECT ''pumper_dashboard_new.reconciliation.manage''
     UNION ALL SELECT ''pumper_dashboard_new.ledger.view''
     UNION ALL SELECT ''pumper_dashboard_new.documents.view''
     UNION ALL SELECT ''pumper_dashboard_new.documents.manage''
     UNION ALL SELECT ''pumper_dashboard_new.login_attempts.view''
     UNION ALL SELECT ''pumper_dashboard_new.login_attempts.manage''
     UNION ALL SELECT ''pumper_dashboard_new.print_logs.view''
     UNION ALL SELECT ''pumper_dashboard_new.reports.view''
     UNION ALL SELECT ''pumper_dashboard_new.reports.export''
     UNION ALL SELECT ''pumper_dashboard_new.reports.print''
     UNION ALL SELECT ''pumper_dashboard_new.reports.shifts''
     UNION ALL SELECT ''pumper_dashboard_new.reports.payments''
     UNION ALL SELECT ''pumper_dashboard_new.reports.meters''
     UNION ALL SELECT ''pumper_dashboard_new.reports.other_sales''
     UNION ALL SELECT ''pumper_dashboard_new.reports.unloads''
     UNION ALL SELECT ''pumper_dashboard_new.reports.day_entries''
     UNION ALL SELECT ''pumper_dashboard_new.reports.collections''
     UNION ALL SELECT ''pumper_dashboard_new.reports.ledger''
     UNION ALL SELECT ''pumper_dashboard_new.reports.shortages''
     UNION ALL SELECT ''pumper_dashboard_new.reports.commissions''
     UNION ALL SELECT ''pumper_dashboard_new.reports.print_logs''
     UNION ALL SELECT ''pumper_dashboard_new.reports.audit''
     UNION ALL SELECT ''pumper_dashboard_new.integration.view''
     UNION ALL SELECT ''pumper_dashboard_new.integration.manage''
     UNION ALL SELECT ''pumper_dashboard_new.settings.manage''
     UNION ALL SELECT ''pumper_dashboard_new.operator.use''
   ) p
   WHERE NOT EXISTS (
     SELECT 1 FROM `permissions` existing
     WHERE existing.`name` = p.name AND existing.`guard_name` = ''web''
   )',
  'SELECT ''Pumper Dashboard-New: permissions table not found; schema installation completed without permission rows.'' AS message'
);
PREPARE pone_permission_stmt FROM @pone_permission_sql;
EXECUTE pone_permission_stmt;
DEALLOCATE PREPARE pone_permission_stmt;

SELECT 'Pumper Dashboard-New 2.0.0 full schema is ready.' AS message;
