-- Dealer Management - Multi-Distributor Dealer Hub
-- Prefix: dlr_
-- Prefer Laravel migrations 000004 + 000005. This SQL is supplied for manual/import use.

CREATE TABLE IF NOT EXISTS dlr_hub_dealers (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, hub_code VARCHAR(30) NOT NULL, name VARCHAR(191) NOT NULL,
 mobile VARCHAR(50) NULL, email VARCHAR(191) NULL, address TEXT NULL, status VARCHAR(20) NOT NULL DEFAULT 'active', notes TEXT NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, PRIMARY KEY(id), UNIQUE KEY dlr_hub_dealers_code_uq(hub_code), KEY dlr_hub_dealers_status_idx(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_hub_outlets (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, hub_dealer_id BIGINT UNSIGNED NOT NULL, outlet_code VARCHAR(30) NOT NULL, name VARCHAR(191) NOT NULL,
 address TEXT NULL, mobile VARCHAR(50) NULL, is_default TINYINT(1) NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1, notes TEXT NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, PRIMARY KEY(id), UNIQUE KEY dlr_hub_outlet_code_uq(hub_dealer_id,outlet_code), KEY dlr_hub_outlet_active_idx(hub_dealer_id,is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_hub_users (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, hub_dealer_id BIGINT UNSIGNED NOT NULL, name VARCHAR(191) NOT NULL, login_code CHAR(4) NOT NULL,
 mobile VARCHAR(50) NULL, email VARCHAR(191) NULL, password VARCHAR(255) NOT NULL, role_name VARCHAR(100) NOT NULL DEFAULT 'Staff', permissions_json JSON NULL,
 is_hub_admin TINYINT(1) NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1, must_change_password TINYINT(1) NOT NULL DEFAULT 1,
 last_login_at DATETIME NULL, password_reset_at DATETIME NULL, notes TEXT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 PRIMARY KEY(id), UNIQUE KEY dlr_hub_user_login_uq(hub_dealer_id,login_code), KEY dlr_hub_users_active_idx(hub_dealer_id,is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_hub_user_outlets (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, hub_user_id BIGINT UNSIGNED NOT NULL, hub_outlet_id BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, PRIMARY KEY(id), UNIQUE KEY dlr_hub_user_outlet_uq(hub_user_id,hub_outlet_id), KEY dlr_hub_user_outlet_idx(hub_outlet_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_hub_distributors (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, distributor_code VARCHAR(30) NOT NULL, name VARCHAR(191) NOT NULL, database_name VARCHAR(120) NOT NULL,
 business_id BIGINT UNSIGNED NOT NULL, base_url VARCHAR(500) NULL, status VARCHAR(20) NOT NULL DEFAULT 'active', last_seen_at TIMESTAMP NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, PRIMARY KEY(id), UNIQUE KEY dlr_hub_distributor_code_uq(distributor_code), UNIQUE KEY dlr_hub_distributor_db_business_uq(database_name,business_id), KEY dlr_hub_distributor_status_idx(status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_hub_connections (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, hub_dealer_id BIGINT UNSIGNED NOT NULL, distributor_id BIGINT UNSIGNED NOT NULL, local_dealer_id BIGINT UNSIGNED NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'pending', invitation_code VARCHAR(12) NULL, connection_source VARCHAR(30) NOT NULL DEFAULT 'distributor_invite',
 invited_at DATETIME NULL, approved_at DATETIME NULL, approved_by_hub_user_id BIGINT UNSIGNED NULL, notes TEXT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 PRIMARY KEY(id), UNIQUE KEY dlr_hub_connection_uq(hub_dealer_id,distributor_id), UNIQUE KEY dlr_hub_connection_invite_uq(invitation_code), KEY dlr_hub_connection_status_idx(distributor_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_hub_product_sources (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, hub_dealer_id BIGINT UNSIGNED NOT NULL, hub_outlet_id BIGINT UNSIGNED NOT NULL, distributor_id BIGINT UNSIGNED NOT NULL,
 local_dealer_id BIGINT UNSIGNED NULL, local_outlet_id BIGINT UNSIGNED NULL, local_product_id BIGINT UNSIGNED NOT NULL, local_variation_id BIGINT UNSIGNED NULL,
 product_key VARCHAR(191) NOT NULL, product_name VARCHAR(191) NOT NULL, sku VARCHAR(100) NULL, allocation_method VARCHAR(30) NOT NULL DEFAULT 'fifo',
 source_qty DECIMAL(22,4) NOT NULL DEFAULT 0, reorder_level DECIMAL(22,4) NOT NULL DEFAULT 0, is_active TINYINT(1) NOT NULL DEFAULT 1,
 last_synced_at DATETIME NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, PRIMARY KEY(id),
 UNIQUE KEY dlr_hub_product_source_uq(hub_dealer_id,hub_outlet_id,distributor_id,local_product_id,local_variation_id),
 KEY dlr_hub_product_key_idx(hub_dealer_id,hub_outlet_id,product_key), KEY dlr_hub_ps_fast_idx(hub_dealer_id,hub_outlet_id,is_active,distributor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_hub_sales_entries (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, hub_dealer_id BIGINT UNSIGNED NOT NULL, hub_outlet_id BIGINT UNSIGNED NOT NULL, entry_no VARCHAR(60) NOT NULL,
 sale_date DATE NOT NULL, status VARCHAR(30) NOT NULL DEFAULT 'posted', notes TEXT NULL, submitted_by BIGINT UNSIGNED NOT NULL, submitted_at DATETIME NOT NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, PRIMARY KEY(id), UNIQUE KEY dlr_hub_sales_entry_no_uq(entry_no), KEY dlr_hub_sales_scope_idx(hub_dealer_id,hub_outlet_id,sale_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_hub_sales_entry_lines (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, sales_entry_id BIGINT UNSIGNED NOT NULL, product_key VARCHAR(191) NOT NULL, product_name VARCHAR(191) NOT NULL,
 sku VARCHAR(100) NULL, opening_qty DECIMAL(22,4) NOT NULL DEFAULT 0, received_qty DECIMAL(22,4) NOT NULL DEFAULT 0, sold_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
 return_qty DECIMAL(22,4) NOT NULL DEFAULT 0, damaged_qty DECIMAL(22,4) NOT NULL DEFAULT 0, closing_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
 allocation_method VARCHAR(30) NOT NULL DEFAULT 'fifo', notes TEXT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 PRIMARY KEY(id), KEY dlr_hub_sales_line_entry_idx(sales_entry_id), KEY dlr_hub_sales_line_product_idx(product_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_hub_sales_allocations (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, sales_entry_line_id BIGINT UNSIGNED NOT NULL, distributor_id BIGINT UNSIGNED NOT NULL, product_source_id BIGINT UNSIGNED NOT NULL,
 allocated_sold_qty DECIMAL(22,4) NOT NULL DEFAULT 0, allocated_return_qty DECIMAL(22,4) NOT NULL DEFAULT 0, allocated_damage_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
 allocation_method VARCHAR(30) NOT NULL, sync_status VARCHAR(30) NOT NULL DEFAULT 'pending', sync_error TEXT NULL, synced_at DATETIME NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 PRIMARY KEY(id), KEY dlr_hub_alloc_source_idx(product_source_id), KEY dlr_hub_alloc_sync_idx(sync_status,distributor_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_hub_orders (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, hub_dealer_id BIGINT UNSIGNED NOT NULL, hub_outlet_id BIGINT UNSIGNED NOT NULL, hub_order_no VARCHAR(60) NOT NULL,
 order_date DATE NOT NULL, requested_delivery_date DATE NULL, status VARCHAR(30) NOT NULL DEFAULT 'submitted', notes TEXT NULL, submitted_by BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, PRIMARY KEY(id), UNIQUE KEY dlr_hub_order_no_uq(hub_order_no), KEY dlr_hub_order_scope_idx(hub_dealer_id,status,order_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_hub_order_lines (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, hub_order_id BIGINT UNSIGNED NOT NULL, product_key VARCHAR(191) NOT NULL, product_name VARCHAR(191) NOT NULL,
 preferred_distributor_id BIGINT UNSIGNED NULL, current_qty DECIMAL(22,4) NOT NULL DEFAULT 0, suggested_qty DECIMAL(22,4) NOT NULL DEFAULT 0, requested_qty DECIMAL(22,4) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, PRIMARY KEY(id), KEY dlr_hub_order_line_order_idx(hub_order_id), KEY dlr_hub_order_line_product_idx(product_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_hub_split_orders (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, hub_order_id BIGINT UNSIGNED NOT NULL, distributor_id BIGINT UNSIGNED NOT NULL, local_order_id BIGINT UNSIGNED NULL,
 local_order_no VARCHAR(60) NULL, status VARCHAR(30) NOT NULL DEFAULT 'pending', payload_json JSON NULL, sync_error TEXT NULL, synced_at DATETIME NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, PRIMARY KEY(id), UNIQUE KEY dlr_hub_split_order_uq(hub_order_id,distributor_id), KEY dlr_hub_split_sync_idx(status,distributor_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_hub_notifications (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, hub_dealer_id BIGINT UNSIGNED NOT NULL, hub_user_id BIGINT UNSIGNED NULL, distributor_id BIGINT UNSIGNED NULL,
 type VARCHAR(50) NOT NULL, severity VARCHAR(20) NOT NULL DEFAULT 'info', title VARCHAR(191) NOT NULL, message TEXT NOT NULL, action_url VARCHAR(500) NULL,
 is_read TINYINT(1) NOT NULL DEFAULT 0, read_at DATETIME NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 PRIMARY KEY(id), KEY dlr_hub_notification_scope_idx(hub_dealer_id,is_read,created_at), KEY dlr_hub_notification_type_idx(type,severity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_hub_delivery_feed (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, hub_dealer_id BIGINT UNSIGNED NOT NULL, hub_outlet_id BIGINT UNSIGNED NULL, distributor_id BIGINT UNSIGNED NOT NULL,
 reference_no VARCHAR(100) NULL, delivery_at DATETIME NULL, status VARCHAR(30) NOT NULL DEFAULT 'delivered', payload_json JSON NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 PRIMARY KEY(id), UNIQUE KEY dlr_hub_delivery_ref_uq(distributor_id,reference_no), KEY dlr_hub_delivery_scope_idx(hub_dealer_id,delivery_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_hub_return_feed (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, hub_dealer_id BIGINT UNSIGNED NOT NULL, hub_outlet_id BIGINT UNSIGNED NULL, distributor_id BIGINT UNSIGNED NOT NULL,
 reference_no VARCHAR(100) NULL, return_at DATETIME NULL, status VARCHAR(30) NOT NULL DEFAULT 'processed', payload_json JSON NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 PRIMARY KEY(id), UNIQUE KEY dlr_hub_return_ref_uq(distributor_id,reference_no), KEY dlr_hub_return_scope_idx(hub_dealer_id,return_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dlr_hub_audit_logs (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, hub_dealer_id BIGINT UNSIGNED NULL, hub_user_id BIGINT UNSIGNED NULL, event VARCHAR(100) NOT NULL,
 entity_type VARCHAR(100) NULL, entity_id BIGINT UNSIGNED NULL, ip_address VARCHAR(64) NULL, user_agent VARCHAR(500) NULL,
 old_values JSON NULL, new_values JSON NULL, created_at TIMESTAMP NULL, PRIMARY KEY(id), KEY dlr_hub_audit_scope_idx(hub_dealer_id,event)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Existing local Dealer Management tables also need these nullable links.
-- Use Laravel migration 000004 for safe conditional ALTERs on existing installations.
