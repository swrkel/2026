Customers Module - Standalone Separation - Phase 8
=================================================

Scope
-----
This phase adds Customer Attachments / Document Management to the standalone Customers module.

Safety Rules Followed
---------------------
1. No files were removed from Contacts.
2. No Contacts module files were changed.
3. Customer master data still uses the existing contacts table with type = customer.
4. All customer data remains centralized under business_id / main head office.
5. Branch/location remains only an assignment/filter/reporting layer.
6. Attachment records are stored under the central business_id and optional location_id.

Files Added / Updated
---------------------
- Routes/web.php
- Http/Controllers/CustomerAttachmentController.php
- Http/Controllers/CustomerController.php
- Resources/views/customers/show.blade.php
- Resources/lang/en/lang.php
- Database/Migrations/2026_06_04_000003_create_customer_attachments_table.php

Raw SQL Option
--------------
Run this SQL in phpMyAdmin if you are not using migrations:

CREATE TABLE IF NOT EXISTS customer_attachments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT UNSIGNED NOT NULL,
    customer_id INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED NULL,
    title VARCHAR(191) NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(191) NULL,
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY customer_attachments_business_id_index (business_id),
    KEY customer_attachments_customer_id_index (customer_id),
    KEY customer_attachments_location_id_index (location_id),
    KEY customer_attachments_created_by_index (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

Testing
-------
1. Open Customers > Customer Register.
2. Open any customer profile.
3. Upload a PDF/JPG/PNG/Word/Excel/CSV/TXT file.
4. Confirm the attachment appears in the Customer Attachments table.
5. Download the attachment.
6. Delete the attachment.
7. Confirm activity history records attachment added/deleted events if Phase 7 activity table is enabled.

Upload Path
-----------
Files are saved under:
public/uploads/customers/{business_id}/{customer_id}/

After Upload
------------
Run:
php artisan view:clear
php artisan cache:clear
