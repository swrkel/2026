-- LOAN-27 Loan Products enhancement columns
-- Run in the tenant database if these columns are missing.

ALTER TABLE loan_products
    ADD COLUMN processing_fee DECIMAL(22,4) NULL AFTER default_interest_rate,
    ADD COLUMN late_payment_charge DECIMAL(22,4) NULL AFTER processing_fee,
    ADD COLUMN notes TEXT NULL AFTER description;
