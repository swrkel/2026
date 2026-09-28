-- RestaurantNew Stage 043 Corporate Accounts - CREATE tables
-- Run on each tenant database.
CREATE TABLE IF NOT EXISTS restaurant_new_corporate_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  account_code VARCHAR(50) NOT NULL,
  company_name VARCHAR(255) NOT NULL,
  contact_person VARCHAR(255) NULL,
  mobile VARCHAR(30) NULL,
  email VARCHAR(255) NULL,
  credit_limit DECIMAL(22,4) NOT NULL DEFAULT 0,
  current_balance DECIMAL(22,4) NOT NULL DEFAULT 0,
  credit_days INT NOT NULL DEFAULT 0,
  status ENUM('active','hold','closed') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY restnew_corp_account_code_unique (business_id, account_code),
  KEY restnew_corp_account_scope_idx (business_id, location_id, status)
);
CREATE TABLE IF NOT EXISTS restaurant_new_corporate_contract_prices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  corporate_account_id BIGINT UNSIGNED NOT NULL,
  menu_item_id BIGINT UNSIGNED NULL,
  menu_category_id BIGINT UNSIGNED NULL,
  contract_price DECIMAL(22,4) NULL,
  discount_percent DECIMAL(8,4) NOT NULL DEFAULT 0,
  effective_from DATE NULL,
  effective_to DATE NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  KEY restnew_corp_price_scope_idx (business_id, corporate_account_id, status)
);
CREATE TABLE IF NOT EXISTS restaurant_new_corporate_invoices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  corporate_account_id BIGINT UNSIGNED NOT NULL,
  invoice_no VARCHAR(80) NOT NULL,
  invoice_date DATE NOT NULL,
  due_date DATE NULL,
  subtotal DECIMAL(22,4) NOT NULL DEFAULT 0,
  tax_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  discount_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  grand_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  paid_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  balance_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  status ENUM('draft','issued','partially_paid','paid','cancelled') NOT NULL DEFAULT 'draft',
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY restnew_corp_invoice_no_unique (business_id, invoice_no),
  KEY restnew_corp_invoice_scope_idx (business_id, location_id, status, invoice_date)
);
CREATE TABLE IF NOT EXISTS restaurant_new_corporate_invoice_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  corporate_invoice_id BIGINT UNSIGNED NOT NULL,
  restaurant_order_id BIGINT UNSIGNED NULL,
  description VARCHAR(255) NOT NULL,
  qty DECIMAL(22,4) NOT NULL DEFAULT 1,
  unit_price DECIMAL(22,4) NOT NULL DEFAULT 0,
  line_total DECIMAL(22,4) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  KEY restnew_corp_invoice_line_idx (business_id, corporate_invoice_id)
);
CREATE TABLE IF NOT EXISTS restaurant_new_corporate_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  corporate_account_id BIGINT UNSIGNED NOT NULL,
  corporate_invoice_id BIGINT UNSIGNED NULL,
  payment_date DATE NOT NULL,
  method VARCHAR(50) NULL,
  reference_no VARCHAR(120) NULL,
  amount DECIMAL(22,4) NOT NULL DEFAULT 0,
  notes TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  KEY restnew_corp_payment_scope_idx (business_id, corporate_account_id, payment_date)
);
