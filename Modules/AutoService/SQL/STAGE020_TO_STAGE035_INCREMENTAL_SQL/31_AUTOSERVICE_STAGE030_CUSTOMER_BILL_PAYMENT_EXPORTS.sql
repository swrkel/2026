/*
 AutoService Stage 030 - Customer Bill, Payment History and Export Tools
 Tenant database SQL only. Safe to run on each tenant database. No database name is hardcoded.
*/

-- Customer portal settings for bill/payment/export visibility
INSERT INTO auto_service_settings (`key`, `value`, `created_at`, `updated_at`)
SELECT 'allow_customer_service_summary_print', '1', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key` = 'allow_customer_service_summary_print');

INSERT INTO auto_service_settings (`key`, `value`, `created_at`, `updated_at`)
SELECT 'allow_customer_parts_history_export', '1', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key` = 'allow_customer_parts_history_export');

INSERT INTO auto_service_settings (`key`, `value`, `created_at`, `updated_at`)
SELECT 'allow_customer_payment_history_view', '1', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM auto_service_settings WHERE `key` = 'allow_customer_payment_history_view');

-- Optional permission keys for staff-side visibility and support. Insert only if permissions table exists in tenant DB.
INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.customer_portal.exports', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
AND NOT EXISTS (SELECT 1 FROM permissions WHERE `name` = 'autoservice.customer_portal.exports');

INSERT INTO permissions (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'autoservice.customer_portal.payment_history', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'permissions')
AND NOT EXISTS (SELECT 1 FROM permissions WHERE `name` = 'autoservice.customer_portal.payment_history');

-- Performance indexes for customer portal bill/history lookups. Add manually if your MySQL version does not support IF NOT EXISTS for indexes.
ALTER TABLE auto_service_payments ADD INDEX IF NOT EXISTS idx_as_payments_invoice_date (invoice_id, payment_date);
ALTER TABLE auto_service_invoice_lines ADD INDEX IF NOT EXISTS idx_as_invoice_lines_type_product (line_type, product_id);
ALTER TABLE auto_service_job_lines ADD INDEX IF NOT EXISTS idx_as_job_lines_type_product (line_type, product_id);
ALTER TABLE auto_service_part_movements ADD INDEX IF NOT EXISTS idx_as_part_movements_job_date (job_id, movement_date);
