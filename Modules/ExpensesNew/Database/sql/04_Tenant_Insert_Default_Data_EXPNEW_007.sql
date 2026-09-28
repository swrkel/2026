-- EXPNEW_007 Idempotent Default Data
INSERT INTO expnew_integration_sources (business_id, source_module, display_name, is_active, created_at, updated_at)
SELECT NULL, 'HRManager', 'HR Manager', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_integration_sources WHERE business_id IS NULL AND source_module='HRManager');
INSERT INTO expnew_integration_sources (business_id, source_module, display_name, is_active, created_at, updated_at)
SELECT NULL, 'POS', 'Point of Sale', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_integration_sources WHERE business_id IS NULL AND source_module='POS');
INSERT INTO expnew_notification_templates (business_id, event_key, subject, body, channels_json, is_active, created_at, updated_at)
SELECT NULL, 'expense.submitted', 'Expense Submitted', 'An expense has been submitted for approval.', '["system","email"]', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_notification_templates WHERE business_id IS NULL AND event_key='expense.submitted');
INSERT INTO expnew_notification_templates (business_id, event_key, subject, body, channels_json, is_active, created_at, updated_at)
SELECT NULL, 'expense.approved', 'Expense Approved', 'An expense has been approved.', '["system","email"]', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_notification_templates WHERE business_id IS NULL AND event_key='expense.approved');
INSERT INTO expnew_notification_templates (business_id, event_key, subject, body, channels_json, is_active, created_at, updated_at)
SELECT NULL, 'expense.paid', 'Expense Paid', 'An expense payment has been completed.', '["system","sms","email"]', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_notification_templates WHERE business_id IS NULL AND event_key='expense.paid');
