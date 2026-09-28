# Migration Changes - Commit `23740f127`

This document summarizes the migration-related changes introduced in commit:

- `23740f1279575b16152e5e7641cdd88af5521614`

## Scope

Only migration files were considered.  
Source reference used: `tmp_commit_23740f127_migrations.diff`.

## New Migration Files Added

### MPCS module migrations

1. `Modules/MPCS/Database/Migrations/2026_05_03_000001_create_form_f25_settings_table.php`
2. `Modules/MPCS/Database/Migrations/2026_05_03_000002_create_form_f25_delivery_locations_table.php`
3. `Modules/MPCS/Database/Migrations/2026_05_03_000003_create_form_f25_headers_table.php`

### Core VAT-related migrations

1. `database/migrations/2026_05_04_000006_add_address_to_vat_contacts_table.php`
2. `database/migrations/2026_05_04_000006_alter_vat_prefix_fields_for_leading_zeros.php`
3. `database/migrations/2026_05_04_000007_add_expense_code_to_vat_expense_categories_table.php`

## Existing Migration Files Modified

### Added `Schema::hasTable(...)` guards before `Schema::create(...)`

1. `database/migrations/2025_01_10_120000_create_settlement_pos_payments_table.php`
2. `database/migrations/2025_05_31_213714_create_account_transactions_table.php`
3. `database/migrations/2025_05_31_213714_create_account_types_table.php`
4. `database/migrations/2025_05_31_213714_create_accounts_table.php`
5. `database/migrations/2025_05_31_213714_create_activity_log_table.php`
6. `database/migrations/2025_05_31_213714_create_ad_page_slots_table.php`
7. `database/migrations/2025_05_31_213714_create_ad_pages_table.php`
8. `database/migrations/2025_05_31_213714_create_additional_service_table.php`
9. `database/migrations/2025_05_31_213714_create_ads_table.php`
10. `database/migrations/2025_05_31_213714_create_agents_table.php`
11. `database/migrations/2025_05_31_213714_create_air_ticket_invoices_table.php`
12. `database/migrations/2025_05_31_213714_create_airline_add_commissions_table.php`
13. `database/migrations/2025_05_31_213714_create_airline_agents_table.php`
14. `database/migrations/2025_05_31_213714_create_airline_airports_table.php`
15. `database/migrations/2025_05_31_213714_create_airline_classes_table.php`

## Schema Impact Summary

### New tables

- `form_f25_settings`
- `form_f25_delivery_locations`
- `form_f25_headers`

### Actual table structures (new tables)

#### `form_f25_settings`

- `id` `BIGINT UNSIGNED` auto increment, primary key
- `business_id` `INT UNSIGNED` (indexed with `opening_date`)
- `opening_date` `DATE`
- `starting_number` `INT UNSIGNED`
- `created_by` `INT UNSIGNED NULL`
- `created_at` / `updated_at` timestamps
- Index: `INDEX (business_id, opening_date)`

#### `form_f25_delivery_locations`

- `id` `BIGINT UNSIGNED` auto increment, primary key
- `business_id` `INT UNSIGNED`
- `code` `VARCHAR(10)`
- `name` `VARCHAR(255)`
- `is_active` `TINYINT(1)` default `1`
- `created_by` `INT UNSIGNED NULL`
- `created_at` / `updated_at` timestamps
- Unique key: `UNIQUE (business_id, code)`
- Index: `INDEX (business_id, is_active)`

#### `form_f25_headers`

- `id` `BIGINT UNSIGNED` auto increment, primary key
- `business_id` `INT UNSIGNED`
- `location_id` `INT UNSIGNED NULL`
- `form_no` `VARCHAR(255)`
- `form_date` `DATE`
- `supplier_id` `INT UNSIGNED NULL`
- `bill_no` `VARCHAR(255) NULL`
- `delivery_location_id` `BIGINT UNSIGNED NULL`
- `created_by` `INT UNSIGNED NULL`
- `created_at` / `updated_at` timestamps
- Index: `INDEX (business_id, form_date)`

### New columns

- `vat_contacts.address` (nullable string)
- `vat_expense_categories.expense_code` (nullable string)

### Actual altered column structures

#### `vat_contacts`

- Added column:
- `address` `VARCHAR(255) NULL` (added after `vat_no`)

#### `vat_expense_categories`

- Added column:
- `expense_code` `VARCHAR(255) NULL` (added after `name`)

### Altered VAT prefix column definitions

For each table below:

- `vat_prefixes`
- `vat_invoice2_prefixes`
- `vat_statement_prefixes`

`up()` changes:

- `prefix` -> `VARCHAR(191) NULL`
- `starting_no` -> `VARCHAR(191) NOT NULL DEFAULT '1'`

`down()` rollback:

- `prefix` -> `VARCHAR(10) NULL`
- `starting_no` -> `INT NOT NULL DEFAULT 1`

### Existing table-create migrations hardened

The following existing create migrations were structurally updated with a guard:

- `if (Schema::hasTable('<table_name>')) { return; }`

This does not change the column design itself; it prevents duplicate table creation when tables already exist.

## Before vs After

### New MPCS tables

#### `form_f25_settings`

- Before: table did not exist
- After:
- `id`, `business_id`, `opening_date`, `starting_number`, `created_by`, timestamps
- index on `(business_id, opening_date)`

#### `form_f25_delivery_locations`

- Before: table did not exist
- After:
- `id`, `business_id`, `code`, `name`, `is_active`, `created_by`, timestamps
- unique key on `(business_id, code)`
- index on `(business_id, is_active)`

#### `form_f25_headers`

- Before: table did not exist
- After:
- `id`, `business_id`, `location_id`, `form_no`, `form_date`, `supplier_id`, `bill_no`, `delivery_location_id`, `created_by`, timestamps
- index on `(business_id, form_date)`

### VAT contacts

- Before:
- no `address` column on `vat_contacts`
- After:
- added `address VARCHAR(255) NULL` (positioned after `vat_no`)

### VAT expense categories

- Before:
- no `expense_code` column on `vat_expense_categories`
- After:
- added `expense_code VARCHAR(255) NULL` (positioned after `name`)

### VAT prefix tables

Tables affected:

- `vat_prefixes`
- `vat_invoice2_prefixes`
- `vat_statement_prefixes`

#### Column `prefix`

- Before: `VARCHAR(10) NULL`
- After: `VARCHAR(191) NULL`

#### Column `starting_no`

- Before: `INT NOT NULL DEFAULT 1`
- After: `VARCHAR(191) NOT NULL DEFAULT '1'`

### Existing create migrations (guard hardening)

- Before:
- migration directly executed `Schema::create(...)`
- if table already existed, migration could fail
- After:
- migration first checks `Schema::hasTable('<table_name>')`
- if table exists, migration returns early and skips create

## Notes

- The `Schema::hasTable(...)` guards make legacy create migrations idempotent in environments where tables may already exist.
- The VAT prefix migration intentionally stores `starting_no` as string to preserve leading zeros.
- Rollback of VAT prefix changes may drop leading zero formatting when converting string values back to integer.

## SQL Scripts

### Forward SQL (UP)

```sql
-- 1) New table: form_f25_settings
CREATE TABLE IF NOT EXISTS `form_f25_settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `opening_date` DATE NOT NULL,
  `starting_number` INT UNSIGNED NOT NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_form_f25_settings_business_opening` (`business_id`, `opening_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) New table: form_f25_delivery_locations
CREATE TABLE IF NOT EXISTS `form_f25_delivery_locations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `code` VARCHAR(10) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_form_f25_delivery_locations_business_code` (`business_id`, `code`),
  KEY `idx_form_f25_delivery_locations_business_active` (`business_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) New table: form_f25_headers
CREATE TABLE IF NOT EXISTS `form_f25_headers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` INT UNSIGNED NOT NULL,
  `location_id` INT UNSIGNED NULL,
  `form_no` VARCHAR(255) NOT NULL,
  `form_date` DATE NOT NULL,
  `supplier_id` INT UNSIGNED NULL,
  `bill_no` VARCHAR(255) NULL,
  `delivery_location_id` BIGINT UNSIGNED NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_form_f25_headers_business_form_date` (`business_id`, `form_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4) Add column: vat_contacts.address
ALTER TABLE `vat_contacts`
  ADD COLUMN `address` VARCHAR(255) NULL AFTER `vat_no`;

-- 5) Add column: vat_expense_categories.expense_code
ALTER TABLE `vat_expense_categories`
  ADD COLUMN `expense_code` VARCHAR(255) NULL AFTER `name`;

-- 6) Alter VAT prefix tables
ALTER TABLE `vat_prefixes`
  MODIFY `prefix` VARCHAR(191) NULL,
  MODIFY `starting_no` VARCHAR(191) NOT NULL DEFAULT '1';

ALTER TABLE `vat_invoice2_prefixes`
  MODIFY `prefix` VARCHAR(191) NULL,
  MODIFY `starting_no` VARCHAR(191) NOT NULL DEFAULT '1';

ALTER TABLE `vat_statement_prefixes`
  MODIFY `prefix` VARCHAR(191) NULL,
  MODIFY `starting_no` VARCHAR(191) NOT NULL DEFAULT '1';
```

### Rollback SQL (DOWN)

```sql
-- Rollback added columns
ALTER TABLE `vat_contacts` DROP COLUMN `address`;
ALTER TABLE `vat_expense_categories` DROP COLUMN `expense_code`;

-- Rollback VAT prefix field types
ALTER TABLE `vat_prefixes`
  MODIFY `prefix` VARCHAR(10) NULL,
  MODIFY `starting_no` INT NOT NULL DEFAULT 1;

ALTER TABLE `vat_invoice2_prefixes`
  MODIFY `prefix` VARCHAR(10) NULL,
  MODIFY `starting_no` INT NOT NULL DEFAULT 1;

ALTER TABLE `vat_statement_prefixes`
  MODIFY `prefix` VARCHAR(10) NULL,
  MODIFY `starting_no` INT NOT NULL DEFAULT 1;

-- Optional rollback for new tables
DROP TABLE IF EXISTS `form_f25_headers`;
DROP TABLE IF EXISTS `form_f25_delivery_locations`;
DROP TABLE IF EXISTS `form_f25_settings`;
```
