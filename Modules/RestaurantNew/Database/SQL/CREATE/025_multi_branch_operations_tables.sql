CREATE TABLE IF NOT EXISTS restaurant_new_branch_operation_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  business_location_id BIGINT UNSIGNED NOT NULL,
  is_central_kitchen TINYINT(1) NOT NULL DEFAULT 0,
  is_commissary TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  operating_days JSON NULL,
  production_start_time TIME NULL,
  production_end_time TIME NULL,
  meta JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_branch_profile_business_idx (business_id),
  INDEX rn_branch_profile_location_idx (business_location_id)
);

CREATE TABLE IF NOT EXISTS restaurant_new_branch_recipe_policies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  business_location_id BIGINT UNSIGNED NULL,
  restaurant_new_menu_item_id BIGINT UNSIGNED NULL,
  allow_local_override TINYINT(1) NOT NULL DEFAULT 0,
  approval_required TINYINT(1) NOT NULL DEFAULT 1,
  effective_from DATE NULL,
  meta JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_branch_recipe_policy_business_idx (business_id),
  INDEX rn_branch_recipe_policy_location_idx (business_location_id)
);

CREATE TABLE IF NOT EXISTS restaurant_new_branch_transfers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  transfer_no VARCHAR(191) NOT NULL,
  transfer_date DATE NULL,
  from_business_location_id BIGINT UNSIGNED NOT NULL,
  to_business_location_id BIGINT UNSIGNED NOT NULL,
  transfer_type VARCHAR(80) NOT NULL DEFAULT 'branch_transfer',
  status VARCHAR(80) NOT NULL DEFAULT 'draft',
  requested_by BIGINT UNSIGNED NULL,
  requested_at TIMESTAMP NULL,
  approved_by BIGINT UNSIGNED NULL,
  approved_at TIMESTAMP NULL,
  dispatched_by BIGINT UNSIGNED NULL,
  dispatched_at TIMESTAMP NULL,
  received_by BIGINT UNSIGNED NULL,
  received_at TIMESTAMP NULL,
  cancelled_by BIGINT UNSIGNED NULL,
  cancelled_at TIMESTAMP NULL,
  totals JSON NULL,
  meta JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_branch_transfers_business_idx (business_id),
  INDEX rn_branch_transfers_no_idx (transfer_no),
  INDEX rn_branch_transfers_status_idx (status)
);

CREATE TABLE IF NOT EXISTS restaurant_new_branch_transfer_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  restaurant_new_branch_transfer_id BIGINT UNSIGNED NOT NULL,
  ingredient_id BIGINT UNSIGNED NULL,
  menu_item_id BIGINT UNSIGNED NULL,
  line_type VARCHAR(80) NOT NULL DEFAULT 'ingredient',
  requested_qty DECIMAL(20,4) NOT NULL DEFAULT 0,
  approved_qty DECIMAL(20,4) NOT NULL DEFAULT 0,
  dispatched_qty DECIMAL(20,4) NOT NULL DEFAULT 0,
  received_qty DECIMAL(20,4) NOT NULL DEFAULT 0,
  unit_cost DECIMAL(20,4) NOT NULL DEFAULT 0,
  line_total DECIMAL(20,4) NOT NULL DEFAULT 0,
  uom VARCHAR(80) NULL,
  meta JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_branch_transfer_lines_transfer_idx (restaurant_new_branch_transfer_id)
);

CREATE TABLE IF NOT EXISTS restaurant_new_branch_comparison_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  business_location_id BIGINT UNSIGNED NOT NULL,
  snapshot_date DATE NOT NULL,
  sales_total DECIMAL(20,4) NOT NULL DEFAULT 0,
  food_cost_total DECIMAL(20,4) NOT NULL DEFAULT 0,
  gross_profit_total DECIMAL(20,4) NOT NULL DEFAULT 0,
  wastage_total DECIMAL(20,4) NOT NULL DEFAULT 0,
  kpi_payload JSON NULL,
  meta JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_branch_snapshot_business_idx (business_id),
  INDEX rn_branch_snapshot_location_date_idx (business_location_id, snapshot_date)
);
