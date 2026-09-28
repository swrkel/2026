-- LOAN-10 Loan Customer extra fields and image columns
-- Run in each tenant database only if these columns do not already exist.

ALTER TABLE `loan_customers`
  ADD COLUMN `photo` varchar(255) NULL AFTER `notes`,
  ADD COLUMN `nic_front_image` varchar(255) NULL AFTER `photo`,
  ADD COLUMN `nic_back_image` varchar(255) NULL AFTER `nic_front_image`,
  ADD COLUMN `signature_image` varchar(255) NULL AFTER `nic_back_image`;

-- If your database still has status as ENUM('active','inactive'), run the line below:
ALTER TABLE `loan_customers`
  MODIFY COLUMN `status` varchar(30) NOT NULL DEFAULT 'active';

-- Keep missing email values professional.
UPDATE `loan_customers`
SET `email` = 'No Email ID'
WHERE `email` IS NULL OR TRIM(`email`) = '';
