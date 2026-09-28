-- EzyLaw V3 incremental tenant schema
-- Apply to each applicable TENANT database only. Do not run in the central registry database.
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS law_workflow_templates (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(191) NOT NULL,
 practice_area_id BIGINT UNSIGNED NULL,
 description TEXT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_workflow_templates_business(business_id), KEY idx_law_workflow_templates_area(practice_area_id), KEY idx_law_workflow_templates_active(active)
);

CREATE TABLE IF NOT EXISTS law_workflow_stages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 workflow_template_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(191) NOT NULL,
 sequence_no INT UNSIGNED NOT NULL DEFAULT 1,
 default_days INT UNSIGNED NULL,
 required TINYINT(1) NOT NULL DEFAULT 0,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_workflow_stages_business(business_id), KEY idx_law_workflow_stages_template(workflow_template_id), KEY idx_law_workflow_stages_active(active)
);

CREATE TABLE IF NOT EXISTS law_matter_stage_history (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 matter_id BIGINT UNSIGNED NOT NULL,
 workflow_stage_id BIGINT UNSIGNED NULL,
 stage_name VARCHAR(191) NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'active',
 started_at DATETIME NOT NULL,
 due_at DATETIME NULL,
 completed_at DATETIME NULL,
 notes TEXT NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_matter_stage_business(business_id), KEY idx_law_matter_stage_matter(matter_id), KEY idx_law_matter_stage_stage(workflow_stage_id), KEY idx_law_matter_stage_status(status), KEY idx_law_matter_stage_due(due_at)
);

CREATE TABLE IF NOT EXISTS law_deadlines (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 matter_id BIGINT UNSIGNED NULL,
 client_id BIGINT UNSIGNED NULL,
 deadline_type VARCHAR(80) NOT NULL,
 title VARCHAR(191) NOT NULL,
 basis_date DATE NULL,
 due_at DATETIME NOT NULL,
 warning_days INT UNSIGNED NOT NULL DEFAULT 7,
 status VARCHAR(30) NOT NULL DEFAULT 'open',
 source_type VARCHAR(80) NULL,
 source_id BIGINT UNSIGNED NULL,
 assigned_user_id BIGINT UNSIGNED NULL,
 notes TEXT NULL,
 completed_at DATETIME NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_deadlines_business(business_id), KEY idx_law_deadlines_matter(matter_id), KEY idx_law_deadlines_client(client_id), KEY idx_law_deadlines_type(deadline_type), KEY idx_law_deadlines_due(due_at), KEY idx_law_deadlines_status(status), KEY idx_law_deadlines_user(assigned_user_id)
);

CREATE TABLE IF NOT EXISTS law_evidence_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 matter_id BIGINT UNSIGNED NOT NULL,
 evidence_no VARCHAR(80) NOT NULL,
 title VARCHAR(191) NOT NULL,
 evidence_type VARCHAR(80) NOT NULL,
 description TEXT NULL,
 received_on DATE NULL,
 custody_location VARCHAR(191) NULL,
 confidential TINYINT(1) NOT NULL DEFAULT 0,
 status VARCHAR(30) NOT NULL DEFAULT 'received',
 document_id BIGINT UNSIGNED NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 UNIQUE KEY uq_law_evidence_no(business_id,matter_id,evidence_no),
 KEY idx_law_evidence_business(business_id), KEY idx_law_evidence_matter(matter_id), KEY idx_law_evidence_type(evidence_type), KEY idx_law_evidence_received(received_on), KEY idx_law_evidence_status(status), KEY idx_law_evidence_document(document_id)
);

CREATE TABLE IF NOT EXISTS law_document_versions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 document_id BIGINT UNSIGNED NOT NULL,
 version_no INT UNSIGNED NOT NULL,
 file_name VARCHAR(191) NOT NULL,
 file_path VARCHAR(191) NOT NULL,
 mime_type VARCHAR(150) NULL,
 file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
 change_note TEXT NULL,
 uploaded_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 UNIQUE KEY uq_law_document_version(business_id,document_id,version_no),
 KEY idx_law_doc_versions_business(business_id), KEY idx_law_doc_versions_document(document_id)
);

CREATE TABLE IF NOT EXISTS law_billing_rates (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 practice_area_id BIGINT UNSIGNED NULL,
 matter_id BIGINT UNSIGNED NULL,
 rate_type VARCHAR(30) NOT NULL DEFAULT 'hourly',
 hourly_rate DECIMAL(22,4) NOT NULL DEFAULT 0,
 fixed_rate DECIMAL(22,4) NOT NULL DEFAULT 0,
 effective_from DATE NULL,
 effective_to DATE NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_billing_rates_business(business_id), KEY idx_law_billing_rates_user(user_id), KEY idx_law_billing_rates_area(practice_area_id), KEY idx_law_billing_rates_matter(matter_id), KEY idx_law_billing_rates_dates(effective_from,effective_to), KEY idx_law_billing_rates_active(active)
);

CREATE TABLE IF NOT EXISTS law_invoice_adjustments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 invoice_id BIGINT UNSIGNED NOT NULL,
 adjustment_type VARCHAR(20) NOT NULL,
 amount DECIMAL(22,4) NOT NULL,
 reason TEXT NOT NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_invoice_adjustments_business(business_id), KEY idx_law_invoice_adjustments_invoice(invoice_id), KEY idx_law_invoice_adjustments_type(adjustment_type)
);

CREATE TABLE IF NOT EXISTS law_trust_reconciliations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 trust_account_id BIGINT UNSIGNED NOT NULL,
 statement_date DATE NOT NULL,
 statement_balance DECIMAL(22,4) NOT NULL,
 book_balance DECIMAL(22,4) NOT NULL,
 difference DECIMAL(22,4) NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'open',
 reconciled_by BIGINT UNSIGNED NULL,
 reconciled_at DATETIME NULL,
 notes TEXT NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_trust_reconciliations_business(business_id), KEY idx_law_trust_reconciliations_account(trust_account_id), KEY idx_law_trust_reconciliations_date(statement_date), KEY idx_law_trust_reconciliations_status(status)
);

CREATE TABLE IF NOT EXISTS law_trust_reconciliation_lines (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 reconciliation_id BIGINT UNSIGNED NOT NULL,
 trust_transaction_id BIGINT UNSIGNED NOT NULL,
 line_type VARCHAR(20) NOT NULL,
 amount DECIMAL(22,4) NOT NULL,
 matched TINYINT(1) NOT NULL DEFAULT 0,
 note TEXT NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_trust_rec_lines_business(business_id), KEY idx_law_trust_rec_lines_reconciliation(reconciliation_id), KEY idx_law_trust_rec_lines_transaction(trust_transaction_id), KEY idx_law_trust_rec_lines_matched(matched)
);

CREATE TABLE IF NOT EXISTS law_portal_access (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 client_id BIGINT UNSIGNED NOT NULL,
 contact_id BIGINT UNSIGNED NULL,
 email VARCHAR(191) NOT NULL,
 token_hash VARCHAR(64) NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'active',
 last_login_at DATETIME NULL,
 expires_at DATETIME NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 UNIQUE KEY uq_law_portal_access(business_id,client_id,email),
 KEY idx_law_portal_access_business(business_id), KEY idx_law_portal_access_client(client_id), KEY idx_law_portal_access_contact(contact_id), KEY idx_law_portal_access_email(email), KEY idx_law_portal_access_status(status), KEY idx_law_portal_access_expiry(expires_at)
);

CREATE TABLE IF NOT EXISTS law_portal_messages (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 client_id BIGINT UNSIGNED NOT NULL,
 matter_id BIGINT UNSIGNED NULL,
 direction VARCHAR(20) NOT NULL DEFAULT 'outbound',
 subject VARCHAR(191) NULL,
 body LONGTEXT NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'logged',
 sent_at DATETIME NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_portal_messages_business(business_id), KEY idx_law_portal_messages_client(client_id), KEY idx_law_portal_messages_matter(matter_id), KEY idx_law_portal_messages_direction(direction), KEY idx_law_portal_messages_status(status), KEY idx_law_portal_messages_sent(sent_at)
);

CREATE TABLE IF NOT EXISTS law_notifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 client_id BIGINT UNSIGNED NULL,
 matter_id BIGINT UNSIGNED NULL,
 type VARCHAR(50) NOT NULL,
 title VARCHAR(191) NOT NULL,
 body LONGTEXT NULL,
 channel VARCHAR(30) NOT NULL DEFAULT 'in_app',
 status VARCHAR(30) NOT NULL DEFAULT 'pending',
 scheduled_at DATETIME NULL,
 sent_at DATETIME NULL,
 read_at DATETIME NULL,
 source_type VARCHAR(80) NULL,
 source_id BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_notifications_business(business_id), KEY idx_law_notifications_user(user_id), KEY idx_law_notifications_client(client_id), KEY idx_law_notifications_matter(matter_id), KEY idx_law_notifications_type(type), KEY idx_law_notifications_channel(channel), KEY idx_law_notifications_status(status), KEY idx_law_notifications_scheduled(scheduled_at), KEY idx_law_notifications_source(source_id)
);

SET FOREIGN_KEY_CHECKS=1;
