-- EzyLaw V2 full tenant database schema. All EzyLaw-owned tables use law_ prefix.
-- Run in EACH tenant database if migrations are not used.
SET FOREIGN_KEY_CHECKS=0;
CREATE TABLE IF NOT EXISTS law_clients (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,location_id BIGINT UNSIGNED NULL,client_no VARCHAR(50) NOT NULL,client_type VARCHAR(30) NOT NULL DEFAULT 'individual',name VARCHAR(191) NOT NULL,company_name VARCHAR(191) NULL,nic_passport VARCHAR(100) NULL,registration_no VARCHAR(100) NULL,email VARCHAR(191) NULL,phone VARCHAR(50) NULL,mobile VARCHAR(50) NULL,address_line_1 VARCHAR(191) NULL,address_line_2 VARCHAR(191) NULL,city VARCHAR(100) NULL,state VARCHAR(100) NULL,country VARCHAR(100) NULL,postal_code VARCHAR(30) NULL,tax_no VARCHAR(100) NULL,contact_person VARCHAR(191) NULL,status VARCHAR(30) NOT NULL DEFAULT 'active',notes TEXT NULL,created_by BIGINT UNSIGNED NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,deleted_at TIMESTAMP NULL,UNIQUE KEY uq_law_client_no(business_id,client_no),KEY idx_law_clients_business(business_id),KEY idx_law_clients_location(location_id));
CREATE TABLE IF NOT EXISTS law_practice_areas (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,name VARCHAR(191) NOT NULL,code VARCHAR(50) NULL,description TEXT NULL,active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,UNIQUE KEY uq_law_area(business_id,name));
CREATE TABLE IF NOT EXISTS law_courts (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,name VARCHAR(191) NOT NULL,code VARCHAR(50) NULL,court_type VARCHAR(100) NULL,address VARCHAR(191) NULL,city VARCHAR(100) NULL,phone VARCHAR(50) NULL,email VARCHAR(191) NULL,active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,KEY idx_law_courts_business(business_id));
CREATE TABLE IF NOT EXISTS law_matters (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,location_id BIGINT UNSIGNED NULL,matter_no VARCHAR(50) NOT NULL,client_id BIGINT UNSIGNED NOT NULL,practice_area_id BIGINT UNSIGNED NULL,court_id BIGINT UNSIGNED NULL,case_no VARCHAR(100) NULL,title VARCHAR(191) NOT NULL,description TEXT NULL,status VARCHAR(30) NOT NULL DEFAULT 'open',priority VARCHAR(30) NOT NULL DEFAULT 'normal',opened_on DATE NOT NULL,closed_on DATE NULL,assigned_user_id BIGINT UNSIGNED NULL,responsible_lawyer_id BIGINT UNSIGNED NULL,opposing_party VARCHAR(191) NULL,opposing_counsel VARCHAR(191) NULL,estimated_value DECIMAL(22,4) NOT NULL DEFAULT 0,fee_type VARCHAR(30) NOT NULL DEFAULT 'hourly',hourly_rate DECIMAL(22,4) NOT NULL DEFAULT 0,fixed_fee DECIMAL(22,4) NOT NULL DEFAULT 0,retainer_amount DECIMAL(22,4) NOT NULL DEFAULT 0,created_by BIGINT UNSIGNED NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,deleted_at TIMESTAMP NULL,UNIQUE KEY uq_law_matter_no(business_id,matter_no),KEY idx_law_matters_business(business_id),KEY idx_law_matters_client(client_id));
CREATE TABLE IF NOT EXISTS law_matter_parties (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,matter_id BIGINT UNSIGNED NOT NULL,party_type VARCHAR(50) NOT NULL,name VARCHAR(191) NOT NULL,role VARCHAR(100) NULL,phone VARCHAR(50) NULL,email VARCHAR(191) NULL,address TEXT NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,KEY idx_law_party_matter(matter_id));
CREATE TABLE IF NOT EXISTS law_hearings (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,matter_id BIGINT UNSIGNED NOT NULL,court_id BIGINT UNSIGNED NULL,hearing_at DATETIME NOT NULL,hearing_type VARCHAR(100) NULL,judge_name VARCHAR(191) NULL,purpose TEXT NULL,outcome TEXT NULL,next_hearing_at DATETIME NULL,status VARCHAR(30) NOT NULL DEFAULT 'scheduled',reminder_minutes INT NOT NULL DEFAULT 1440,created_by BIGINT UNSIGNED NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,KEY idx_law_hearing_business(business_id),KEY idx_law_hearing_date(hearing_at));
CREATE TABLE IF NOT EXISTS law_tasks (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,matter_id BIGINT UNSIGNED NULL,assigned_user_id BIGINT UNSIGNED NULL,title VARCHAR(191) NOT NULL,description TEXT NULL,due_at DATETIME NULL,priority VARCHAR(30) NOT NULL DEFAULT 'normal',status VARCHAR(30) NOT NULL DEFAULT 'open',completed_at DATETIME NULL,created_by BIGINT UNSIGNED NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,KEY idx_law_tasks_business(business_id),KEY idx_law_tasks_due(due_at));
CREATE TABLE IF NOT EXISTS law_time_entries (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,matter_id BIGINT UNSIGNED NOT NULL,user_id BIGINT UNSIGNED NOT NULL,work_date DATE NOT NULL,minutes INT UNSIGNED NOT NULL,rate DECIMAL(22,4) NOT NULL DEFAULT 0,amount DECIMAL(22,4) NOT NULL DEFAULT 0,billable TINYINT(1) NOT NULL DEFAULT 1,invoiced TINYINT(1) NOT NULL DEFAULT 0,description TEXT NOT NULL,created_by BIGINT UNSIGNED NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,KEY idx_law_time_business(business_id),KEY idx_law_time_matter(matter_id));
CREATE TABLE IF NOT EXISTS law_expenses (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,matter_id BIGINT UNSIGNED NULL,expense_date DATE NOT NULL,category VARCHAR(100) NOT NULL,description TEXT NOT NULL,amount DECIMAL(22,4) NOT NULL,billable TINYINT(1) NOT NULL DEFAULT 0,invoiced TINYINT(1) NOT NULL DEFAULT 0,finance_sync_status VARCHAR(30) NOT NULL DEFAULT 'pending',finance_reference VARCHAR(100) NULL,created_by BIGINT UNSIGNED NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,KEY idx_law_expenses_business(business_id));
CREATE TABLE IF NOT EXISTS law_appointments (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,client_id BIGINT UNSIGNED NULL,matter_id BIGINT UNSIGNED NULL,title VARCHAR(191) NOT NULL,start_at DATETIME NOT NULL,end_at DATETIME NULL,appointment_type VARCHAR(80) NULL,location VARCHAR(191) NULL,status VARCHAR(30) NOT NULL DEFAULT 'scheduled',assigned_user_id BIGINT UNSIGNED NULL,notes TEXT NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,KEY idx_law_appointments_business(business_id));
CREATE TABLE IF NOT EXISTS law_invoices (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,location_id BIGINT UNSIGNED NULL,client_id BIGINT UNSIGNED NOT NULL,matter_id BIGINT UNSIGNED NULL,invoice_no VARCHAR(50) NOT NULL,invoice_date DATE NOT NULL,due_date DATE NULL,subtotal DECIMAL(22,4) NOT NULL DEFAULT 0,tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0,discount_amount DECIMAL(22,4) NOT NULL DEFAULT 0,total DECIMAL(22,4) NOT NULL DEFAULT 0,paid_amount DECIMAL(22,4) NOT NULL DEFAULT 0,balance DECIMAL(22,4) NOT NULL DEFAULT 0,status VARCHAR(30) NOT NULL DEFAULT 'unpaid',finance_sync_status VARCHAR(30) NOT NULL DEFAULT 'pending',finance_reference VARCHAR(100) NULL,notes TEXT NULL,created_by BIGINT UNSIGNED NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,UNIQUE KEY uq_law_invoice_no(business_id,invoice_no));
CREATE TABLE IF NOT EXISTS law_invoice_lines (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,invoice_id BIGINT UNSIGNED NOT NULL,source_type VARCHAR(50) NOT NULL DEFAULT 'manual',source_id BIGINT UNSIGNED NULL,description TEXT NOT NULL,qty DECIMAL(18,4) NOT NULL DEFAULT 1,unit_price DECIMAL(22,4) NOT NULL DEFAULT 0,tax_rate DECIMAL(12,4) NOT NULL DEFAULT 0,line_total DECIMAL(22,4) NOT NULL DEFAULT 0,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,KEY idx_law_invoice_lines_invoice(invoice_id));
CREATE TABLE IF NOT EXISTS law_payments (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,invoice_id BIGINT UNSIGNED NOT NULL,client_id BIGINT UNSIGNED NOT NULL,matter_id BIGINT UNSIGNED NULL,payment_date DATE NOT NULL,amount DECIMAL(22,4) NOT NULL,method VARCHAR(50) NOT NULL,reference VARCHAR(100) NULL,finance_sync_status VARCHAR(30) NOT NULL DEFAULT 'pending',finance_reference VARCHAR(100) NULL,notes TEXT NULL,created_by BIGINT UNSIGNED NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,KEY idx_law_payments_invoice(invoice_id));
CREATE TABLE IF NOT EXISTS law_documents (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,matter_id BIGINT UNSIGNED NULL,client_id BIGINT UNSIGNED NULL,category VARCHAR(100) NULL,title VARCHAR(191) NOT NULL,file_name VARCHAR(191) NOT NULL,file_path VARCHAR(191) NOT NULL,mime_type VARCHAR(150) NULL,file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,version INT UNSIGNED NOT NULL DEFAULT 1,confidential TINYINT(1) NOT NULL DEFAULT 0,uploaded_by BIGINT UNSIGNED NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,KEY idx_law_docs_business(business_id));
CREATE TABLE IF NOT EXISTS law_notes (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,matter_id BIGINT UNSIGNED NULL,client_id BIGINT UNSIGNED NULL,note_type VARCHAR(80) NOT NULL DEFAULT 'general',body LONGTEXT NOT NULL,confidential TINYINT(1) NOT NULL DEFAULT 0,created_by BIGINT UNSIGNED NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,KEY idx_law_notes_business(business_id));
CREATE TABLE IF NOT EXISTS law_settings (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,location_id BIGINT UNSIGNED NULL,setting_key VARCHAR(120) NOT NULL,setting_value LONGTEXT NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,UNIQUE KEY uq_law_settings(business_id,location_id,setting_key));
CREATE TABLE IF NOT EXISTS law_integration_queue (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,target_module VARCHAR(80) NOT NULL,event_type VARCHAR(100) NOT NULL,aggregate_type VARCHAR(80) NOT NULL,aggregate_id BIGINT UNSIGNED NOT NULL,payload_json LONGTEXT NULL,status VARCHAR(30) NOT NULL DEFAULT 'pending',attempts INT UNSIGNED NOT NULL DEFAULT 0,last_error TEXT NULL,processed_at DATETIME NULL,created_at TIMESTAMP NULL,updated_at TIMESTAMP NULL,KEY idx_law_integration_business(business_id),KEY idx_law_integration_status(status));
CREATE TABLE IF NOT EXISTS law_activity_logs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,business_id BIGINT UNSIGNED NOT NULL,matter_id BIGINT UNSIGNED NULL,client_id BIGINT UNSIGNED NULL,user_id BIGINT UNSIGNED NULL,action VARCHAR(80) NOT NULL,subject_type VARCHAR(100) NOT NULL,subject_id BIGINT UNSIGNED NULL,description TEXT NULL,metadata_json LONGTEXT NULL,created_at DATETIME NOT NULL,KEY idx_law_activity_business(business_id));

-- ===== EzyLaw V2 legal workflow extension =====
CREATE TABLE IF NOT EXISTS law_chronology_entries (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 matter_id BIGINT UNSIGNED NOT NULL,
 event_at DATETIME NOT NULL,
 event_type VARCHAR(80) NOT NULL DEFAULT 'general',
 title VARCHAR(191) NOT NULL,
 description TEXT NULL,
 source_type VARCHAR(80) NULL,
 source_id BIGINT UNSIGNED NULL,
 is_key_event TINYINT(1) NOT NULL DEFAULT 0,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_chronology_business(business_id), KEY idx_law_chronology_matter(matter_id), KEY idx_law_chronology_event(event_at), KEY idx_law_chronology_key(is_key_event)
);

CREATE TABLE IF NOT EXISTS law_retainers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 client_id BIGINT UNSIGNED NOT NULL,
 matter_id BIGINT UNSIGNED NULL,
 retainer_no VARCHAR(50) NOT NULL,
 agreement_date DATE NOT NULL,
 start_date DATE NULL, end_date DATE NULL,
 retainer_type VARCHAR(50) NOT NULL DEFAULT 'general',
 agreed_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
 replenishment_threshold DECIMAL(22,4) NOT NULL DEFAULT 0,
 status VARCHAR(30) NOT NULL DEFAULT 'active',
 notes TEXT NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 UNIQUE KEY uq_law_retainer_no(business_id,retainer_no), KEY idx_law_retainers_client(client_id), KEY idx_law_retainers_matter(matter_id), KEY idx_law_retainers_status(status)
);

CREATE TABLE IF NOT EXISTS law_trust_accounts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 name VARCHAR(191) NOT NULL,
 account_no VARCHAR(100) NULL,
 bank_name VARCHAR(191) NULL,
 currency VARCHAR(10) NULL,
 current_balance DECIMAL(22,4) NOT NULL DEFAULT 0,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_trust_accounts_business(business_id), KEY idx_law_trust_accounts_location(location_id), KEY idx_law_trust_accounts_active(active)
);

CREATE TABLE IF NOT EXISTS law_trust_transactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 trust_account_id BIGINT UNSIGNED NOT NULL,
 client_id BIGINT UNSIGNED NOT NULL,
 matter_id BIGINT UNSIGNED NULL,
 invoice_id BIGINT UNSIGNED NULL,
 transaction_date DATE NOT NULL,
 type VARCHAR(50) NOT NULL,
 direction VARCHAR(10) NOT NULL,
 amount DECIMAL(22,4) NOT NULL,
 reference VARCHAR(100) NULL,
 payee VARCHAR(191) NULL,
 description TEXT NULL,
 running_balance DECIMAL(22,4) NOT NULL DEFAULT 0,
 finance_sync_status VARCHAR(30) NOT NULL DEFAULT 'pending',
 finance_reference VARCHAR(100) NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_trust_tx_business(business_id), KEY idx_law_trust_tx_account(trust_account_id), KEY idx_law_trust_tx_client(client_id), KEY idx_law_trust_tx_matter(matter_id), KEY idx_law_trust_tx_invoice(invoice_id), KEY idx_law_trust_tx_date(transaction_date), KEY idx_law_trust_tx_type(type), KEY idx_law_trust_tx_direction(direction)
);

CREATE TABLE IF NOT EXISTS law_reminders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 client_id BIGINT UNSIGNED NULL,
 matter_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NULL,
 title VARCHAR(191) NOT NULL,
 remind_at DATETIME NOT NULL,
 channel VARCHAR(30) NOT NULL DEFAULT 'in_app',
 status VARCHAR(30) NOT NULL DEFAULT 'pending',
 repeat_rule VARCHAR(80) NULL,
 source_type VARCHAR(80) NULL,
 source_id BIGINT UNSIGNED NULL,
 notes TEXT NULL,
 sent_at DATETIME NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_reminders_business(business_id), KEY idx_law_reminders_matter(matter_id), KEY idx_law_reminders_date(remind_at), KEY idx_law_reminders_status(status)
);

CREATE TABLE IF NOT EXISTS law_document_templates (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(191) NOT NULL,
 category VARCHAR(100) NULL,
 description TEXT NULL,
 body_html LONGTEXT NOT NULL,
 variables_json LONGTEXT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_templates_business(business_id), KEY idx_law_templates_category(category), KEY idx_law_templates_active(active)
);

CREATE TABLE IF NOT EXISTS law_communications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 client_id BIGINT UNSIGNED NULL,
 matter_id BIGINT UNSIGNED NULL,
 channel VARCHAR(30) NOT NULL,
 direction VARCHAR(20) NOT NULL DEFAULT 'outbound',
 recipient VARCHAR(191) NULL,
 sender VARCHAR(191) NULL,
 subject VARCHAR(191) NULL,
 body LONGTEXT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'logged',
 sent_at DATETIME NULL,
 external_reference VARCHAR(150) NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_comms_business(business_id), KEY idx_law_comms_client(client_id), KEY idx_law_comms_matter(matter_id), KEY idx_law_comms_channel(channel), KEY idx_law_comms_status(status), KEY idx_law_comms_sent(sent_at)
);

CREATE TABLE IF NOT EXISTS law_conflict_checks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 check_no VARCHAR(50) NOT NULL,
 query_name VARCHAR(191) NOT NULL,
 identifiers TEXT NULL,
 requested_by BIGINT UNSIGNED NULL,
 result_status VARCHAR(30) NOT NULL DEFAULT 'clear',
 notes TEXT NULL,
 checked_at DATETIME NOT NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 UNIQUE KEY uq_law_conflict_check_no(business_id,check_no), KEY idx_law_conflicts_business(business_id), KEY idx_law_conflicts_name(query_name), KEY idx_law_conflicts_status(result_status), KEY idx_law_conflicts_checked(checked_at)
);

CREATE TABLE IF NOT EXISTS law_conflict_matches (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 conflict_check_id BIGINT UNSIGNED NOT NULL,
 source_type VARCHAR(80) NOT NULL,
 source_id BIGINT UNSIGNED NULL,
 matched_name VARCHAR(191) NULL,
 matched_field VARCHAR(100) NULL,
 match_score DECIMAL(8,4) NOT NULL DEFAULT 0,
 relationship VARCHAR(100) NULL,
 notes TEXT NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 KEY idx_law_conflict_matches_business(business_id), KEY idx_law_conflict_matches_check(conflict_check_id), KEY idx_law_conflict_matches_source(source_type,source_id)
);

-- ===== EzyLaw V3 advanced practice extension =====
-- EzyLaw V3 incremental tenant schema
-- Apply to each applicable TENANT database only. Do not run in the central registry database.

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
