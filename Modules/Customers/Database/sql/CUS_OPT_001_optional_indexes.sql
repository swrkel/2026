-- CUS_OPT_001 optional performance indexes
-- Run only if these indexes do not already exist in your tenant database.
-- If MySQL reports "Duplicate key name", skip that line because the index already exists.

ALTER TABLE contacts ADD INDEX idx_contacts_business_type_deleted_active (business_id, type, deleted_at, active);
ALTER TABLE contacts ADD INDEX idx_contacts_business_contact_id (business_id, contact_id);
ALTER TABLE contacts ADD INDEX idx_contacts_business_name (business_id, name);

ALTER TABLE transactions ADD INDEX idx_transactions_business_contact_date_type_deleted (business_id, contact_id, transaction_date, type, deleted_at);
ALTER TABLE transactions ADD INDEX idx_transactions_business_date_type_deleted (business_id, transaction_date, type, deleted_at);
ALTER TABLE transactions ADD INDEX idx_transactions_contact_payment_status (contact_id, payment_status);

ALTER TABLE transaction_payments ADD INDEX idx_transaction_payments_business_transaction_deleted (business_id, transaction_id, deleted_at);
ALTER TABLE transaction_payments ADD INDEX idx_transaction_payments_business_paymentfor_deleted (business_id, payment_for, deleted_at);
ALTER TABLE transaction_payments ADD INDEX idx_transaction_payments_business_paidon_deleted (business_id, paid_on, deleted_at);

ALTER TABLE contact_ledgers ADD INDEX idx_contact_ledgers_business_contact_date_deleted (business_id, contact_id, transaction_date, deleted_at);

ALTER TABLE transaction_sell_lines ADD INDEX idx_transaction_sell_lines_transaction_deleted (transaction_id, deleted_at);
