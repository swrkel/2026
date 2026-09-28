-- EzyLaw V4 completion release - incremental tenant SQL
-- Apply only to the intended tenant database. Do NOT run in the central registry database.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS law_court_filings (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 business_id BIGINT UNSIGNED NOT NULL,
 matter_id BIGINT UNSIGNED NOT NULL,
 court_id BIGINT UNSIGNED NULL,
 filing_no VARCHAR(80) NOT NULL,
 filing_type VARCHAR(100) NOT NULL,
 title VARCHAR(255) NOT NULL,
 filed_on DATE NULL,
 filed_by_user_id BIGINT UNSIGNED NULL,
 reference_no VARCHAR(120) NULL,
 fee_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
 status VARCHAR(30) NOT NULL DEFAULT 'draft',
 response_due_at DATETIME NULL,
 notes TEXT NULL,
 created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL DEFAULT NULL,
 updated_at TIMESTAMP NULL DEFAULT NULL,
 PRIMARY KEY (id),
 UNIQUE KEY law_court_filings_unique (business_id,matter_id,filing_no),
 KEY law_court_filings_business_id_index (business_id), KEY law_court_filings_matter_id_index (matter_id), KEY law_court_filings_court_id_index (court_id),
 KEY law_court_filings_filing_no_index (filing_no), KEY law_court_filings_filing_type_index (filing_type), KEY law_court_filings_filed_on_index (filed_on),
 KEY law_court_filings_filed_by_user_id_index (filed_by_user_id), KEY law_court_filings_reference_no_index (reference_no), KEY law_court_filings_status_index (status),
 KEY law_court_filings_response_due_at_index (response_due_at), KEY law_court_filings_created_by_index (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS law_settlements (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, business_id BIGINT UNSIGNED NOT NULL, matter_id BIGINT UNSIGNED NOT NULL, client_id BIGINT UNSIGNED NULL,
 settlement_no VARCHAR(80) NOT NULL, settlement_type VARCHAR(60) NOT NULL DEFAULT 'negotiation', offer_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
 agreed_amount DECIMAL(22,4) NOT NULL DEFAULT 0, status VARCHAR(30) NOT NULL DEFAULT 'draft', offered_on DATE NULL, accepted_on DATE NULL, completed_on DATE NULL,
 terms LONGTEXT NULL, confidential TINYINT(1) NOT NULL DEFAULT 0, created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
 PRIMARY KEY(id), UNIQUE KEY law_settlements_business_id_settlement_no_unique (business_id,settlement_no), KEY law_settlements_business_id_index (business_id),
 KEY law_settlements_matter_id_index (matter_id), KEY law_settlements_client_id_index (client_id), KEY law_settlements_settlement_no_index (settlement_no),
 KEY law_settlements_settlement_type_index (settlement_type), KEY law_settlements_status_index (status), KEY law_settlements_offered_on_index (offered_on),
 KEY law_settlements_accepted_on_index (accepted_on), KEY law_settlements_completed_on_index (completed_on), KEY law_settlements_confidential_index (confidential), KEY law_settlements_created_by_index (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS law_mediation_sessions (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, business_id BIGINT UNSIGNED NOT NULL, settlement_id BIGINT UNSIGNED NULL, matter_id BIGINT UNSIGNED NOT NULL,
 mediator VARCHAR(255) NULL, venue VARCHAR(255) NULL, session_at DATETIME NOT NULL, status VARCHAR(30) NOT NULL DEFAULT 'scheduled', outcome TEXT NULL,
 next_session_at DATETIME NULL, notes TEXT NULL, created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
 PRIMARY KEY(id), KEY law_mediation_sessions_business_id_index (business_id), KEY law_mediation_sessions_settlement_id_index (settlement_id),
 KEY law_mediation_sessions_matter_id_index (matter_id), KEY law_mediation_sessions_session_at_index (session_at), KEY law_mediation_sessions_status_index (status),
 KEY law_mediation_sessions_next_session_at_index (next_session_at), KEY law_mediation_sessions_created_by_index (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS law_research_items (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, business_id BIGINT UNSIGNED NOT NULL, matter_id BIGINT UNSIGNED NULL, practice_area_id BIGINT UNSIGNED NULL,
 title VARCHAR(255) NOT NULL, citation VARCHAR(255) NULL, source_type VARCHAR(60) NOT NULL DEFAULT 'case', source_url VARCHAR(1000) NULL, court VARCHAR(255) NULL,
 jurisdiction VARCHAR(120) NULL, decision_date DATE NULL, summary LONGTEXT NULL, key_points LONGTEXT NULL, keywords TEXT NULL, confidential TINYINT(1) NOT NULL DEFAULT 0,
 created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
 PRIMARY KEY(id), KEY law_research_items_business_id_index (business_id), KEY law_research_items_matter_id_index (matter_id), KEY law_research_items_practice_area_id_index (practice_area_id),
 KEY law_research_items_citation_index (citation), KEY law_research_items_source_type_index (source_type), KEY law_research_items_jurisdiction_index (jurisdiction),
 KEY law_research_items_decision_date_index (decision_date), KEY law_research_items_confidential_index (confidential), KEY law_research_items_created_by_index (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS law_fee_estimates (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, business_id BIGINT UNSIGNED NOT NULL, client_id BIGINT UNSIGNED NOT NULL, matter_id BIGINT UNSIGNED NULL,
 estimate_no VARCHAR(80) NOT NULL, estimate_date DATE NOT NULL, valid_until DATE NULL, status VARCHAR(30) NOT NULL DEFAULT 'draft', subtotal DECIMAL(22,4) NOT NULL DEFAULT 0,
 tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0, discount_amount DECIMAL(22,4) NOT NULL DEFAULT 0, total DECIMAL(22,4) NOT NULL DEFAULT 0, notes TEXT NULL,
 invoice_id BIGINT UNSIGNED NULL, accepted_at DATETIME NULL, created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
 PRIMARY KEY(id), UNIQUE KEY law_fee_estimates_business_id_estimate_no_unique (business_id,estimate_no), KEY law_fee_estimates_business_id_index (business_id),
 KEY law_fee_estimates_client_id_index (client_id), KEY law_fee_estimates_matter_id_index (matter_id), KEY law_fee_estimates_estimate_no_index (estimate_no),
 KEY law_fee_estimates_estimate_date_index (estimate_date), KEY law_fee_estimates_valid_until_index (valid_until), KEY law_fee_estimates_status_index (status),
 KEY law_fee_estimates_invoice_id_index (invoice_id), KEY law_fee_estimates_created_by_index (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS law_fee_estimate_lines (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, business_id BIGINT UNSIGNED NOT NULL, estimate_id BIGINT UNSIGNED NOT NULL, description TEXT NOT NULL,
 qty DECIMAL(18,4) NOT NULL DEFAULT 1, unit_price DECIMAL(22,4) NOT NULL DEFAULT 0, tax_rate DECIMAL(12,4) NOT NULL DEFAULT 0, line_total DECIMAL(22,4) NOT NULL DEFAULT 0,
 sort_order INT UNSIGNED NOT NULL DEFAULT 0, created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
 PRIMARY KEY(id), KEY law_fee_estimate_lines_business_id_index (business_id), KEY law_fee_estimate_lines_estimate_id_index (estimate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS law_client_advances (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, business_id BIGINT UNSIGNED NOT NULL, client_id BIGINT UNSIGNED NULL, matter_id BIGINT UNSIGNED NULL,
 advance_type VARCHAR(30) NOT NULL DEFAULT 'client', recipient_user_id BIGINT UNSIGNED NULL, advance_no VARCHAR(80) NOT NULL, received_on DATE NOT NULL,
 amount DECIMAL(22,4) NOT NULL, allocated_amount DECIMAL(22,4) NOT NULL DEFAULT 0, balance DECIMAL(22,4) NOT NULL DEFAULT 0, method VARCHAR(50) NOT NULL,
 reference VARCHAR(120) NULL, notes TEXT NULL, status VARCHAR(30) NOT NULL DEFAULT 'active', finance_sync_status VARCHAR(30) NOT NULL DEFAULT 'pending', finance_reference VARCHAR(120) NULL,
 created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
 PRIMARY KEY(id), UNIQUE KEY law_client_advances_business_id_advance_no_unique (business_id,advance_no), KEY law_client_advances_business_id_index (business_id),
 KEY law_client_advances_client_id_index (client_id), KEY law_client_advances_matter_id_index (matter_id), KEY law_client_advances_advance_type_index (advance_type),
 KEY law_client_advances_recipient_user_id_index (recipient_user_id), KEY law_client_advances_advance_no_index (advance_no), KEY law_client_advances_received_on_index (received_on),
 KEY law_client_advances_status_index (status), KEY law_client_advances_finance_sync_status_index (finance_sync_status), KEY law_client_advances_created_by_index (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS law_advance_allocations (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, business_id BIGINT UNSIGNED NOT NULL, advance_id BIGINT UNSIGNED NOT NULL, invoice_id BIGINT UNSIGNED NOT NULL,
 allocated_on DATE NOT NULL, amount DECIMAL(22,4) NOT NULL, notes TEXT NULL, finance_sync_status VARCHAR(30) NOT NULL DEFAULT 'pending', finance_reference VARCHAR(120) NULL,
 created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
 PRIMARY KEY(id), KEY law_advance_allocations_business_id_index (business_id), KEY law_advance_allocations_advance_id_index (advance_id), KEY law_advance_allocations_invoice_id_index (invoice_id),
 KEY law_advance_allocations_allocated_on_index (allocated_on), KEY law_advance_allocations_finance_sync_status_index (finance_sync_status), KEY law_advance_allocations_created_by_index (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS law_document_approvals (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, business_id BIGINT UNSIGNED NOT NULL, document_id BIGINT UNSIGNED NOT NULL, matter_id BIGINT UNSIGNED NULL,
 requested_by BIGINT UNSIGNED NULL, approver_user_id BIGINT UNSIGNED NOT NULL, version_no INT UNSIGNED NULL, status VARCHAR(30) NOT NULL DEFAULT 'pending',
 requested_at DATETIME NOT NULL, responded_at DATETIME NULL, comments TEXT NULL, created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
 PRIMARY KEY(id), KEY law_document_approvals_business_id_index (business_id), KEY law_document_approvals_document_id_index (document_id), KEY law_document_approvals_matter_id_index (matter_id),
 KEY law_document_approvals_requested_by_index (requested_by), KEY law_document_approvals_approver_user_id_index (approver_user_id), KEY law_document_approvals_status_index (status), KEY law_document_approvals_requested_at_index (requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS law_esign_requests (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, business_id BIGINT UNSIGNED NOT NULL, document_id BIGINT UNSIGNED NOT NULL, matter_id BIGINT UNSIGNED NULL, client_id BIGINT UNSIGNED NULL,
 recipient_name VARCHAR(255) NOT NULL, recipient_email VARCHAR(255) NOT NULL, token_hash VARCHAR(64) NOT NULL, version_no INT UNSIGNED NULL, status VARCHAR(30) NOT NULL DEFAULT 'pending',
 requested_at DATETIME NOT NULL, expires_at DATETIME NULL, viewed_at DATETIME NULL, signed_at DATETIME NULL, declined_at DATETIME NULL, signature_name VARCHAR(255) NULL,
 signature_ip VARCHAR(64) NULL, signature_user_agent TEXT NULL, created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
 PRIMARY KEY(id), KEY law_esign_requests_business_id_index (business_id), KEY law_esign_requests_document_id_index (document_id), KEY law_esign_requests_matter_id_index (matter_id),
 KEY law_esign_requests_client_id_index (client_id), KEY law_esign_requests_recipient_email_index (recipient_email), KEY law_esign_requests_token_hash_index (token_hash),
 KEY law_esign_requests_status_index (status), KEY law_esign_requests_requested_at_index (requested_at), KEY law_esign_requests_expires_at_index (expires_at), KEY law_esign_requests_created_by_index (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS law_notification_rules (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, business_id BIGINT UNSIGNED NOT NULL, name VARCHAR(255) NOT NULL, event_key VARCHAR(80) NOT NULL,
 channel VARCHAR(30) NOT NULL DEFAULT 'in_app', days_before INT NOT NULL DEFAULT 0, recipient_type VARCHAR(40) NOT NULL DEFAULT 'assigned_user', user_id BIGINT UNSIGNED NULL,
 active TINYINT(1) NOT NULL DEFAULT 1, conditions_json LONGTEXT NULL, created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
 PRIMARY KEY(id), KEY law_notification_rules_business_id_index (business_id), KEY law_notification_rules_event_key_index (event_key), KEY law_notification_rules_channel_index (channel),
 KEY law_notification_rules_user_id_index (user_id), KEY law_notification_rules_active_index (active), KEY law_notification_rules_created_by_index (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS law_lawyer_targets (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, business_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, period_type VARCHAR(20) NOT NULL DEFAULT 'monthly',
 period_start DATE NOT NULL, period_end DATE NOT NULL, target_hours DECIMAL(18,2) NOT NULL DEFAULT 0, target_billing DECIMAL(22,4) NOT NULL DEFAULT 0,
 target_collections DECIMAL(22,4) NOT NULL DEFAULT 0, target_new_matters INT UNSIGNED NOT NULL DEFAULT 0, cost_rate DECIMAL(22,4) NOT NULL DEFAULT 0,
 notes TEXT NULL, created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
 PRIMARY KEY(id), UNIQUE KEY law_lawyer_targets_unique (business_id,user_id,period_start,period_end), KEY law_lawyer_targets_business_id_index (business_id),
 KEY law_lawyer_targets_user_id_index (user_id), KEY law_lawyer_targets_period_type_index (period_type), KEY law_lawyer_targets_period_start_index (period_start),
 KEY law_lawyer_targets_period_end_index (period_end), KEY law_lawyer_targets_created_by_index (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS law_matter_closures (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, business_id BIGINT UNSIGNED NOT NULL, matter_id BIGINT UNSIGNED NOT NULL, closure_date DATE NOT NULL,
 closure_reason VARCHAR(120) NOT NULL, outcome TEXT NULL, final_fee_amount DECIMAL(22,4) NOT NULL DEFAULT 0, client_notified TINYINT(1) NOT NULL DEFAULT 0,
 documents_archived TINYINT(1) NOT NULL DEFAULT 0, trust_cleared TINYINT(1) NOT NULL DEFAULT 0, billing_cleared TINYINT(1) NOT NULL DEFAULT 0,
 closed_by BIGINT UNSIGNED NULL, reopened_at DATETIME NULL, reopened_by BIGINT UNSIGNED NULL, reopen_reason TEXT NULL, created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL DEFAULT NULL, updated_at TIMESTAMP NULL DEFAULT NULL,
 PRIMARY KEY(id), UNIQUE KEY law_matter_closures_business_id_matter_id_unique (business_id,matter_id), KEY law_matter_closures_business_id_index (business_id),
 KEY law_matter_closures_matter_id_index (matter_id), KEY law_matter_closures_closure_date_index (closure_date), KEY law_matter_closures_closed_by_index (closed_by),
 KEY law_matter_closures_reopened_by_index (reopened_by), KEY law_matter_closures_created_by_index (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS law_portal_audit_logs (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, business_id BIGINT UNSIGNED NOT NULL, client_id BIGINT UNSIGNED NOT NULL, portal_access_id BIGINT UNSIGNED NULL,
 matter_id BIGINT UNSIGNED NULL, action VARCHAR(100) NOT NULL, ip_address VARCHAR(64) NULL, user_agent TEXT NULL, metadata_json LONGTEXT NULL, created_at DATETIME NOT NULL,
 PRIMARY KEY(id), KEY law_portal_audit_logs_business_id_index (business_id), KEY law_portal_audit_logs_client_id_index (client_id),
 KEY law_portal_audit_logs_portal_access_id_index (portal_access_id), KEY law_portal_audit_logs_matter_id_index (matter_id), KEY law_portal_audit_logs_action_index (action),
 KEY law_portal_audit_logs_created_at_index (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
