-- LOAN-34 Collections & Recovery tables
-- Run in the tenant database only if these tables do not already exist.

CREATE TABLE IF NOT EXISTS loan_recovery_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED NULL,
    loan_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    recovery_officer_id BIGINT UNSIGNED NULL,
    assigned_by BIGINT UNSIGNED NULL,
    assigned_at DATETIME NULL,
    status VARCHAR(50) DEFAULT 'active',
    priority VARCHAR(50) DEFAULT 'normal',
    remarks TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX loan_recovery_assignments_business_id_index (business_id),
    INDEX loan_recovery_assignments_location_id_index (location_id),
    INDEX loan_recovery_assignments_loan_id_index (loan_id),
    INDEX loan_recovery_assignments_customer_id_index (customer_id),
    INDEX loan_recovery_assignments_officer_index (recovery_officer_id)
);

CREATE TABLE IF NOT EXISTS loan_collection_actions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED NULL,
    loan_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    action_type VARCHAR(50) NOT NULL,
    action_date DATE NULL,
    next_action_date DATE NULL,
    outcome VARCHAR(100) NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX loan_collection_actions_business_id_index (business_id),
    INDEX loan_collection_actions_location_id_index (location_id),
    INDEX loan_collection_actions_loan_id_index (loan_id),
    INDEX loan_collection_actions_customer_id_index (customer_id)
);

CREATE TABLE IF NOT EXISTS loan_promises_to_pay (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED NULL,
    loan_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    recovery_officer_id BIGINT UNSIGNED NULL,
    ptp_no VARCHAR(50) NULL,
    promised_amount DECIMAL(22,4) DEFAULT 0,
    promised_payment_date DATE NULL,
    status VARCHAR(50) DEFAULT 'open',
    ptp_type VARCHAR(50) NULL,
    source VARCHAR(50) NULL,
    risk_level VARCHAR(50) NULL,
    is_escalated TINYINT(1) DEFAULT 0,
    remarks TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX loan_promises_to_pay_business_id_index (business_id),
    INDEX loan_promises_to_pay_location_id_index (location_id),
    INDEX loan_promises_to_pay_loan_id_index (loan_id),
    INDEX loan_promises_to_pay_customer_id_index (customer_id),
    INDEX loan_promises_to_pay_status_index (status)
);
