-- LOAN-11 Loan Customer ERP Standard Fields
-- Run only in tenant database if your server does not auto-create missing columns.

ALTER TABLE `loan_customers`
  ADD COLUMN `title` varchar(20) NULL AFTER `customer_no`,
  ADD COLUMN `middle_name` varchar(100) NULL AFTER `first_name`,
  ADD COLUMN `date_of_birth` date NULL AFTER `nic`,
  ADD COLUMN `gender` varchar(30) NULL AFTER `date_of_birth`,
  ADD COLUMN `marital_status` varchar(30) NULL AFTER `gender`,
  ADD COLUMN `customer_type` varchar(50) NULL DEFAULT 'individual' AFTER `marital_status`,
  ADD COLUMN `risk_grade` varchar(50) NULL AFTER `customer_type`,
  ADD COLUMN `loan_officer` varchar(100) NULL AFTER `risk_grade`,
  ADD COLUMN `branch_name` varchar(100) NULL AFTER `loan_officer`,
  ADD COLUMN `phone` varchar(30) NULL AFTER `mobile`,
  ADD COLUMN `address_line_2` varchar(255) NULL AFTER `address`,
  ADD COLUMN `district` varchar(100) NULL AFTER `city`,
  ADD COLUMN `province` varchar(100) NULL AFTER `district`,
  ADD COLUMN `postal_code` varchar(20) NULL AFTER `province`,
  ADD COLUMN `occupation` varchar(100) NULL AFTER `postal_code`,
  ADD COLUMN `employer_name` varchar(150) NULL AFTER `occupation`,
  ADD COLUMN `monthly_income` decimal(22,4) NOT NULL DEFAULT 0 AFTER `employer_name`,
  ADD COLUMN `photo` varchar(255) NULL AFTER `notes`,
  ADD COLUMN `nic_front_image` varchar(255) NULL AFTER `photo`,
  ADD COLUMN `nic_back_image` varchar(255) NULL AFTER `nic_front_image`,
  ADD COLUMN `signature_image` varchar(255) NULL AFTER `nic_back_image`,
  ADD COLUMN `updated_by` int unsigned NULL AFTER `created_by`;

ALTER TABLE `loan_customers`
  MODIFY COLUMN `status` varchar(30) NOT NULL DEFAULT 'active';

UPDATE `loan_customers`
SET `email` = 'No Email ID'
WHERE `email` IS NULL OR TRIM(`email`) = '';
