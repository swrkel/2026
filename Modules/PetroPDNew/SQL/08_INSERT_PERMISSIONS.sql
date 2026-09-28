-- Petro PD-New permissions. Safe to rerun.
SET NAMES utf8mb4;
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.access','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.access' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.dashboard.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.dashboard.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.sources.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.sources.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.sources.import','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.sources.import' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.sources.refresh','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.sources.refresh' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.settlements.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.settlements.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.settlements.create','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.settlements.create' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.settlements.edit','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.settlements.edit' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.settlements.cancel','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.settlements.cancel' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.settlements.reopen','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.settlements.reopen' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.payments.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.payments.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.payments.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.payments.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.adjustments.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.adjustments.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.adjustments.request','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.adjustments.request' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.adjustments.approve','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.adjustments.approve' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reconciliation.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reconciliation.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reconciliation.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reconciliation.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.workflow.review','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.workflow.review' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.workflow.approve','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.workflow.approve' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.workflow.finalize','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.workflow.finalize' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.day_end.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.day_end.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.day_end.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.day_end.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.day_end.finalize','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.day_end.finalize' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.operators.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.operators.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.operators.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.operators.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.export','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.export' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.print','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.print' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.settlements','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.settlements' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.shifts','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.shifts' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.operators','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.operators' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.payments','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.payments' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.meters','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.meters' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.other_sales','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.other_sales' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.unloads','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.unloads' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.day_entries','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.day_entries' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.collections','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.collections' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.ledger','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.ledger' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.shortages','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.shortages' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.commissions','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.commissions' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.day_end','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.day_end' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.reconciliation','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.reconciliation' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.adjustments','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.adjustments' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.integrity','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.integrity' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.activity','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.activity' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.integration','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.integration' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.reports.print_history','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.reports.print_history' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.integration.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.integration.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.integration.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.integration.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.notifications.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.notifications.manage' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.audit.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.audit.view' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.print','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.print' AND `guard_name`='web');
INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'petro_pd_new.settings.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='petro_pd_new.settings.manage' AND `guard_name`='web');
