-- AutoService Stage 024: Billing, Payment and Delivery Handover Control
-- Run this on every tenant database. No database name is specified intentionally.

CREATE TABLE IF NOT EXISTS auto_service_delivery_handover (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NULL,
    location_id BIGINT UNSIGNED NULL,
    job_id BIGINT UNSIGNED NOT NULL,
    invoice_id BIGINT UNSIGNED NULL,
    released_by BIGINT UNSIGNED NULL,
    released_at DATETIME NULL,
    odometer_out DECIMAL(20,3) NULL,
    fuel_level_out VARCHAR(50) NULL,
    customer_signature TEXT NULL,
    warranty_note TEXT NULL,
    delivery_note TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX asdh_business_idx (business_id),
    INDEX asdh_location_idx (location_id),
    INDEX asdh_job_idx (job_id),
    INDEX asdh_invoice_idx (invoice_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE auto_service_jobs ADD COLUMN IF NOT EXISTS warranty_note TEXT NULL AFTER delivery_note;
ALTER TABLE auto_service_jobs ADD COLUMN IF NOT EXISTS customer_signature TEXT NULL AFTER warranty_note;
ALTER TABLE auto_service_payments ADD COLUMN IF NOT EXISTS location_id BIGINT UNSIGNED NULL AFTER business_id;
ALTER TABLE auto_service_payments ADD COLUMN IF NOT EXISTS payment_date DATE NULL AFTER invoice_id;
ALTER TABLE auto_service_payments ADD COLUMN IF NOT EXISTS method VARCHAR(50) NULL AFTER payment_date;
ALTER TABLE auto_service_payments ADD COLUMN IF NOT EXISTS reference_no VARCHAR(191) NULL AFTER amount;
ALTER TABLE auto_service_payments ADD COLUMN IF NOT EXISTS note TEXT NULL AFTER reference_no;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.billing_delivery.view', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.billing_delivery.view');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.billing_delivery.manage', 'web', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'autoservice.billing_delivery.manage');
