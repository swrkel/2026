-- Task 8046 Customer Reference - MASTER script
--
-- Run this one file in each tenant database. It sources the individual
-- scripts in the correct order. If your client cannot use SOURCE, run the
-- numbered files below manually, in order.

SOURCE 01_CREATE_CUSTOMER_REFERENCES.sql;

-- Rollback (destructive - drops the table and all references):
--   SOURCE 99_ROLLBACK_DROP_CUSTOMER_REFERENCES.sql;
