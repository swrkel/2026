<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Source SQL: DISNEW_007_RETURNS_CREDIT_NOTES_STATUS_SMS_OFFICERS.sql
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
-- DISNEW_007_RETURNS_CREDIT_NOTES_STATUS_SMS_OFFICERS.sql
-- Distribution New Stage 7 - standalone additions only

CREATE TABLE IF NOT EXISTS disnew_returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    business_location_id BIGINT UNSIGNED NULL,
    sales_order_id BIGINT UNSIGNED NULL,
    sales_invoice_id BIGINT UNSIGNED NULL,
    delivery_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NULL,
    sales_rep_id BIGINT UNSIGNED NULL,
    vehicle_id BIGINT UNSIGNED NULL,
    return_no VARCHAR(50) NOT NULL,
    return_date DATE NOT NULL,
    return_type VARCHAR(30) NOT NULL DEFAULT 'customer_return',
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    reason TEXT NULL,
    subtotal DECIMAL(22,4) NOT NULL DEFAULT 0,
    tax_total DECIMAL(22,4) NOT NULL DEFAULT 0,
    total_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY disnew_returns_business_no_unique (business_id, return_no),
    INDEX disnew_returns_business_status_idx (business_id, status),
    INDEX disnew_returns_invoice_idx (sales_invoice_id),
    INDEX disnew_returns_vehicle_idx (vehicle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_return_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    return_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    variation_id BIGINT UNSIGNED NULL,
    unit_id BIGINT UNSIGNED NULL,
    qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    unit_price DECIMAL(22,4) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    line_total DECIMAL(22,4) NOT NULL DEFAULT 0,
    condition_status VARCHAR(30) NOT NULL DEFAULT 'saleable',
    stock_destination VARCHAR(30) NOT NULL DEFAULT 'store',
    note TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX disnew_return_lines_return_idx (return_id),
    INDEX disnew_return_lines_product_idx (product_id, variation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_credit_notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    business_location_id BIGINT UNSIGNED NULL,
    customer_id BIGINT UNSIGNED NULL,
    sales_invoice_id BIGINT UNSIGNED NULL,
    return_id BIGINT UNSIGNED NULL,
    credit_note_no VARCHAR(50) NOT NULL,
    credit_note_date DATE NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    reason TEXT NULL,
    subtotal DECIMAL(22,4) NOT NULL DEFAULT 0,
    tax_total DECIMAL(22,4) NOT NULL DEFAULT 0,
    total_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    applied_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    balance_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY disnew_credit_notes_business_no_unique (business_id, credit_note_no),
    INDEX disnew_credit_notes_invoice_idx (sales_invoice_id),
    INDEX disnew_credit_notes_return_idx (return_id),
    INDEX disnew_credit_notes_customer_idx (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_credit_note_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    credit_note_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    variation_id BIGINT UNSIGNED NULL,
    description VARCHAR(255) NULL,
    qty DECIMAL(22,4) NOT NULL DEFAULT 0,
    unit_price DECIMAL(22,4) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(22,4) NOT NULL DEFAULT 0,
    line_total DECIMAL(22,4) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX disnew_credit_note_lines_note_idx (credit_note_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_sms_officer_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    business_location_id BIGINT UNSIGNED NULL,
    group_name VARCHAR(100) NOT NULL,
    event_key VARCHAR(80) NOT NULL,
    officer_user_ids TEXT NULL,
    officer_mobile_numbers TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX disnew_sms_officer_groups_event_idx (business_id, event_key, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_status_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    business_id BIGINT UNSIGNED NOT NULL,
    document_type VARCHAR(40) NOT NULL,
    from_status VARCHAR(40) NULL,
    to_status VARCHAR(40) NOT NULL,
    permission_name VARCHAR(120) NULL,
    requires_reason TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX disnew_status_rules_doc_idx (business_id, document_type, from_status, to_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL);
    }

    /**
     * Reverse the migrations.
     * Drops only disnew_ tables created in this stage.
     */
    public function down(): void
    {
        Schema::dropIfExists('disnew_status_rules');
        Schema::dropIfExists('disnew_sms_officer_groups');
        Schema::dropIfExists('disnew_credit_note_lines');
        Schema::dropIfExists('disnew_credit_notes');
        Schema::dropIfExists('disnew_return_lines');
        Schema::dropIfExists('disnew_returns');
    }
};
