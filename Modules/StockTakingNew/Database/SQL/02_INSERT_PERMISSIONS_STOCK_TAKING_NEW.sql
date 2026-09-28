-- Stock Taking - New user permissions. Safe to run repeatedly.
SET NAMES utf8mb4;

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.access','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.access' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.dashboard.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.dashboard.view' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.sessions.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.sessions.view' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.sessions.create','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.sessions.create' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.sessions.edit','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.sessions.edit' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.sessions.prepare','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.sessions.prepare' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.sessions.start','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.sessions.start' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.counts.enter','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.counts.enter' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.counts.import','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.counts.import' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.counts.submit','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.counts.submit' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.recounts.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.recounts.manage' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.approvals.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.approvals.view' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.approvals.approve','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.approvals.approve' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.approvals.reject','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.approvals.reject' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.reconciliation.post','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.reconciliation.post' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.templates.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.templates.manage' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.schedules.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.schedules.manage' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.reports.view','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.reports.view' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.documents.print','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.documents.print' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.documents.share','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.documents.share' AND `guard_name`='web');

INSERT INTO `permissions` (`name`,`guard_name`,`created_at`,`updated_at`)
SELECT 'stock_taking_new.settings.manage','web',NOW(),NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='permissions')
  AND NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name`='stock_taking_new.settings.manage' AND `guard_name`='web');
