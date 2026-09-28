-- LOAN-31 – Loan Disbursement Workflow
-- Run in each tenant database before using Loan Disbursements.
CREATE TABLE IF NOT EXISTS loan_disbursements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT UNSIGNED NULL,
    location_id INT UNSIGNED NULL,
    loan_application_id BIGINT UNSIGNED NULL,
    loan_id BIGINT UNSIGNED NULL,
    loan_customer_id BIGINT UNSIGNED NULL,
    loan_product_id BIGINT UNSIGNED NULL,
    approved_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    disbursement_amount DECIMAL(22,4) NOT NULL DEFAULT 0.0000,
    disbursement_date DATE NULL,
    payment_method VARCHAR(50) NULL,
    reference_no VARCHAR(191) NULL,
    notes TEXT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'disbursed',
    created_by INT UNSIGNED NULL,
    disbursed_by INT UNSIGNED NULL,
    disbursed_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    INDEX loan_disbursements_business_id_index (business_id),
    INDEX loan_disbursements_location_id_index (location_id),
    INDEX loan_disbursements_application_id_index (loan_application_id),
    INDEX loan_disbursements_loan_id_index (loan_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE loan_applications ADD COLUMN IF NOT EXISTS disbursed_by INT UNSIGNED NULL AFTER approved_by, ADD COLUMN IF NOT EXISTS disbursed_at DATETIME NULL AFTER disbursed_by;
ALTER TABLE loans ADD COLUMN IF NOT EXISTS approved_amount DECIMAL(22,4) NULL AFTER principal_amount, ADD COLUMN IF NOT EXISTS disbursed_by INT UNSIGNED NULL AFTER created_by, ADD COLUMN IF NOT EXISTS disbursed_at DATETIME NULL AFTER disbursed_by;
