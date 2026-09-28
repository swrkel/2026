-- EzyLaw V2 incremental schema - run in EACH tenant database only.
-- Requires the EzyLaw V1 law_ tables. Safe to rerun because all creates use IF NOT EXISTS.
SET FOREIGN_KEY_CHECKS=0;

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

SET FOREIGN_KEY_CHECKS=1;
