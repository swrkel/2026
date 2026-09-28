Customers Module - Standalone Separation Phase 7

Scope completed safely:
1. Customer notes foundation added.
2. Customer activity history foundation added.
3. Customer show/profile page enhanced.
4. Branch/location assignment remains only a filter/reporting layer.
5. All customer master data remains centralized under the main/head-office business_id.
6. Contacts module files are not removed or changed.

Optional SQL for phpMyAdmin if you do not run migrations:

CREATE TABLE IF NOT EXISTS customer_notes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT UNSIGNED NOT NULL,
    business_location_id INT UNSIGNED NULL,
    customer_id INT UNSIGNED NOT NULL,
    note_type VARCHAR(50) NOT NULL DEFAULT 'general',
    note TEXT NOT NULL,
    is_private TINYINT(1) NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX customer_notes_business_id_index (business_id),
    INDEX customer_notes_location_id_index (business_location_id),
    INDEX customer_notes_customer_id_index (customer_id),
    INDEX customer_notes_created_by_index (created_by)
);

CREATE TABLE IF NOT EXISTS customer_activity_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    business_id INT UNSIGNED NOT NULL,
    business_location_id INT UNSIGNED NULL,
    customer_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    old_values LONGTEXT NULL,
    new_values LONGTEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    INDEX customer_activity_logs_business_id_index (business_id),
    INDEX customer_activity_logs_location_id_index (business_location_id),
    INDEX customer_activity_logs_customer_id_index (customer_id),
    INDEX customer_activity_logs_action_index (action),
    INDEX customer_activity_logs_created_by_index (created_by)
);

If using Laravel migrations instead, run:
php artisan module:migrate Customers

Then clear cache:
php artisan view:clear
php artisan cache:clear
