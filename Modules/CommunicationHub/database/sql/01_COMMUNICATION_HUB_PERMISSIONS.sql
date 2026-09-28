-- Optional CommunicationHub permissions seed.
-- Run only if your tenant DB has a `permissions` table with `name`, `guard_name`, timestamps.
INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT permission_name, 'web', NOW(), NOW()
FROM (
  SELECT 'communicationhub.dashboard.view' AS permission_name UNION ALL
  SELECT 'communicationhub.providers.view' UNION ALL
  SELECT 'communicationhub.providers.create' UNION ALL
  SELECT 'communicationhub.providers.edit' UNION ALL
  SELECT 'communicationhub.providers.delete' UNION ALL
  SELECT 'communicationhub.templates.view' UNION ALL
  SELECT 'communicationhub.templates.create' UNION ALL
  SELECT 'communicationhub.templates.edit' UNION ALL
  SELECT 'communicationhub.templates.delete' UNION ALL
  SELECT 'communicationhub.queue.view' UNION ALL
  SELECT 'communicationhub.queue.process' UNION ALL
  SELECT 'communicationhub.queue.retry' UNION ALL
  SELECT 'communicationhub.otp.view' UNION ALL
  SELECT 'communicationhub.otp.generate' UNION ALL
  SELECT 'communicationhub.otp.verify' UNION ALL
  SELECT 'communicationhub.reports.view' UNION ALL
  SELECT 'communicationhub.settings.view' UNION ALL
  SELECT 'communicationhub.settings.edit' UNION ALL
  SELECT 'communicationhub.audit.view' UNION ALL
  SELECT 'communicationhub.production.view' UNION ALL
  SELECT 'communicationhub.marketplace.view' UNION ALL
  SELECT 'communicationhub.marketplace.install' UNION ALL
  SELECT 'communicationhub.marketplace.enable' UNION ALL
  SELECT 'communicationhub.marketplace.disable' UNION ALL
  SELECT 'communicationhub.marketplace.sandbox' UNION ALL
  SELECT 'communicationhub.certification.view' UNION ALL
  SELECT 'communicationhub.commercial.dashboard.view' UNION ALL
  SELECT 'communicationhub.commercial.sms_packages.view' UNION ALL
  SELECT 'communicationhub.commercial.sms_packages.create' UNION ALL
  SELECT 'communicationhub.commercial.clients.view' UNION ALL
  SELECT 'communicationhub.commercial.clients.create' UNION ALL
  SELECT 'communicationhub.commercial.wallets.view' UNION ALL
  SELECT 'communicationhub.commercial.refills.view' UNION ALL
  SELECT 'communicationhub.commercial.refills.create' UNION ALL
  SELECT 'communicationhub.commercial.transactions.view' UNION ALL
  SELECT 'communicationhub.commercial.send_sms' UNION ALL
  SELECT 'communicationhub.commercial.bulk_sms' UNION ALL
  SELECT 'communicationhub.commercial.api_tokens.view' UNION ALL
  SELECT 'communicationhub.commercial.api_tokens.create' UNION ALL
  SELECT 'communicationhub.commercial.delivery_reports.view' UNION ALL
  SELECT 'communicationhub.commercial.profit_reports.view'
) AS p
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = p.permission_name AND `guard_name` = 'web');
