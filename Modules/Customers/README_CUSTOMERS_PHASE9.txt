CUSTOMERS MODULE - PHASE 9
==========================

This phase adds Customer Audit Trail, Customer Documents Register, Document Categories,
Document Expiry Tracking foundation, and Customer Timeline.

IMPORTANT:
- No Contacts module files are removed or changed.
- Customer master data remains in contacts table where type = customer.
- All data remains centralized under business_id / main head office.
- business_location_id/location_id is used only for branch assignment, filtering, reports, and audit context.

Files added:
- CustomerAuditTrailController.php
- CustomerDocumentController.php
- CustomerDocumentCategoryController.php
- CustomerTimelineController.php
- customer_document_categories migration
- customer_documents migration
- customer_audit_trails migration
- documents/audit/timeline views

Run after upload:
php artisan view:clear
php artisan cache:clear

Database:
If you use Laravel module migrations, run the module migrations for Customers.
If you prefer raw SQL, use the SQL below only if the tables do not already exist.

RAW SQL:

CREATE TABLE IF NOT EXISTS customer_document_categories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX customer_document_categories_business_id_index (business_id),
    INDEX customer_document_categories_business_active_index (business_id, is_active)
);

CREATE TABLE IF NOT EXISTS customer_documents (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    business_location_id INT UNSIGNED NULL,
    customer_id INT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(255) NULL,
    file_size BIGINT UNSIGNED NULL,
    issue_date DATE NULL,
    expiry_date DATE NULL,
    remarks TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX customer_documents_business_id_index (business_id),
    INDEX customer_documents_location_index (business_location_id),
    INDEX customer_documents_customer_index (customer_id),
    INDEX customer_documents_category_index (category_id),
    INDEX customer_documents_expiry_index (expiry_date),
    INDEX customer_documents_business_customer_index (business_id, customer_id),
    INDEX customer_documents_business_location_index (business_id, business_location_id)
);

CREATE TABLE IF NOT EXISTS customer_audit_trails (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    business_id INT UNSIGNED NOT NULL,
    business_location_id INT UNSIGNED NULL,
    customer_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    old_values LONGTEXT NULL,
    new_values LONGTEXT NULL,
    ip_address VARCHAR(255) NULL,
    user_agent VARCHAR(255) NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    INDEX customer_audit_trails_business_id_index (business_id),
    INDEX customer_audit_trails_location_index (business_location_id),
    INDEX customer_audit_trails_customer_index (customer_id),
    INDEX customer_audit_trails_action_index (action),
    INDEX customer_audit_trails_business_customer_index (business_id, customer_id)
);

Storage:
Documents are stored on the public disk under:
storage/app/public/customer_documents/{business_id}/{customer_id}

If file downloads do not work, run:
php artisan storage:link
