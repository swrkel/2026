CREATE TABLE IF NOT EXISTS restaurant_new_supplier_quotations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL,
 quotation_no VARCHAR(50) NOT NULL, supplier_name VARCHAR(255) NOT NULL, quotation_date DATE NOT NULL, valid_until DATE NULL,
 subtotal DECIMAL(22,4) DEFAULT 0, discount_amount DECIMAL(22,4) DEFAULT 0, tax_amount DECIMAL(22,4) DEFAULT 0, total_amount DECIMAL(22,4) DEFAULT 0,
 status VARCHAR(30) DEFAULT 'draft', created_by BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
 INDEX rn_sq_business_idx (business_id), INDEX rn_sq_location_idx (location_id), INDEX rn_sq_status_idx (status)
);
CREATE TABLE IF NOT EXISTS restaurant_new_supplier_quotation_lines (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, quotation_id BIGINT UNSIGNED NOT NULL, ingredient_id BIGINT UNSIGNED NULL,
 item_name VARCHAR(255) NOT NULL, quantity DECIMAL(22,4) DEFAULT 0, unit VARCHAR(40) NULL, unit_price DECIMAL(22,4) DEFAULT 0, line_total DECIMAL(22,4) DEFAULT 0,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_sql_quote_idx (quotation_id)
);
CREATE TABLE IF NOT EXISTS restaurant_new_purchase_orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, quotation_id BIGINT UNSIGNED NULL,
 po_no VARCHAR(50) NOT NULL, supplier_name VARCHAR(255) NOT NULL, order_date DATE NOT NULL, expected_date DATE NULL,
 subtotal DECIMAL(22,4) DEFAULT 0, tax_amount DECIMAL(22,4) DEFAULT 0, total_amount DECIMAL(22,4) DEFAULT 0, status VARCHAR(30) DEFAULT 'draft', created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_po_business_idx (business_id), INDEX rn_po_location_idx (location_id), INDEX rn_po_status_idx (status)
);
CREATE TABLE IF NOT EXISTS restaurant_new_purchase_order_lines (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, purchase_order_id BIGINT UNSIGNED NOT NULL, ingredient_id BIGINT UNSIGNED NULL,
 item_name VARCHAR(255) NOT NULL, ordered_qty DECIMAL(22,4) DEFAULT 0, received_qty DECIMAL(22,4) DEFAULT 0, unit VARCHAR(40) NULL, unit_cost DECIMAL(22,4) DEFAULT 0, line_total DECIMAL(22,4) DEFAULT 0,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_pol_po_idx (purchase_order_id)
);
CREATE TABLE IF NOT EXISTS restaurant_new_goods_receipts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, purchase_order_id BIGINT UNSIGNED NULL,
 grn_no VARCHAR(50) NOT NULL, received_date DATE NOT NULL, supplier_name VARCHAR(255) NULL, total_amount DECIMAL(22,4) DEFAULT 0, status VARCHAR(30) DEFAULT 'received', received_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_grn_business_idx (business_id), INDEX rn_grn_location_idx (location_id)
);
CREATE TABLE IF NOT EXISTS restaurant_new_goods_receipt_lines (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, goods_receipt_id BIGINT UNSIGNED NOT NULL, ingredient_id BIGINT UNSIGNED NULL,
 item_name VARCHAR(255) NOT NULL, received_qty DECIMAL(22,4) DEFAULT 0, unit VARCHAR(40) NULL, unit_cost DECIMAL(22,4) DEFAULT 0, line_total DECIMAL(22,4) DEFAULT 0, expiry_date DATE NULL, batch_no VARCHAR(255) NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_grnl_grn_idx (goods_receipt_id)
);
CREATE TABLE IF NOT EXISTS restaurant_new_production_batches (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, batch_no VARCHAR(50) NOT NULL, production_type VARCHAR(50) DEFAULT 'kitchen', item_name VARCHAR(255) NOT NULL,
 planned_qty DECIMAL(22,4) DEFAULT 0, produced_qty DECIMAL(22,4) DEFAULT 0, wastage_qty DECIMAL(22,4) DEFAULT 0, total_cost DECIMAL(22,4) DEFAULT 0, status VARCHAR(30) DEFAULT 'planned', created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_pb_business_idx (business_id), INDEX rn_pb_status_idx (status)
);
CREATE TABLE IF NOT EXISTS restaurant_new_commissary_transfers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id BIGINT UNSIGNED NOT NULL, from_location_id BIGINT UNSIGNED NULL, to_location_id BIGINT UNSIGNED NULL, transfer_no VARCHAR(50) NOT NULL, transfer_date DATE NOT NULL, status VARCHAR(30) DEFAULT 'draft', created_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_ct_business_idx (business_id), INDEX rn_ct_status_idx (status)
);
CREATE TABLE IF NOT EXISTS restaurant_new_commissary_transfer_lines (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, transfer_id BIGINT UNSIGNED NOT NULL, ingredient_id BIGINT UNSIGNED NULL, item_name VARCHAR(255) NOT NULL, quantity DECIMAL(22,4) DEFAULT 0, unit VARCHAR(40) NULL, unit_cost DECIMAL(22,4) DEFAULT 0,
 created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX rn_ctl_transfer_idx (transfer_id)
);
