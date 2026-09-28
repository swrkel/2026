-- LOAN-02 Standalone Loan Customers
-- Run this in each tenant database before testing if the tables/columns do not already exist.

CREATE TABLE IF NOT EXISTS `loan_customers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `business_id` int(10) unsigned NOT NULL,
  `customer_no` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `nic` varchar(50) DEFAULT NULL,
  `mobile` varchar(30) DEFAULT NULL,
  `alternate_mobile` varchar(30) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_customers_customer_no_unique` (`customer_no`),
  KEY `loan_customers_business_id_index` (`business_id`),
  KEY `loan_customers_name_index` (`name`),
  KEY `loan_customers_nic_index` (`nic`),
  KEY `loan_customers_mobile_index` (`mobile`),
  KEY `loan_customers_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `loan_applications`
  ADD COLUMN IF NOT EXISTS `loan_customer_id` int(10) unsigned NULL AFTER `customer_id`;

ALTER TABLE `loans`
  ADD COLUMN IF NOT EXISTS `loan_customer_id` int(10) unsigned NULL AFTER `customer_id`;

CREATE INDEX IF NOT EXISTS `loan_applications_loan_customer_id_index` ON `loan_applications` (`loan_customer_id`);
CREATE INDEX IF NOT EXISTS `loans_loan_customer_id_index` ON `loans` (`loan_customer_id`);
