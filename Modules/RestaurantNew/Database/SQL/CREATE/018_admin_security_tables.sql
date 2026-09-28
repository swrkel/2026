CREATE TABLE restaurant_new_feature_settings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 feature_key VARCHAR(100) NOT NULL,
 is_enabled TINYINT(1) NOT NULL DEFAULT 1,
 settings JSON NULL,
 created_by BIGINT UNSIGNED NULL,
 updated_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 UNIQUE KEY restnew_feature_scope_unique (business_id, location_id, feature_key),
 INDEX rn_feature_business_idx (business_id), INDEX rn_feature_location_idx (location_id), INDEX rn_feature_enabled_idx (is_enabled)
);

CREATE TABLE restaurant_new_user_access_rules (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NOT NULL,
 access_area VARCHAR(80) NOT NULL,
 allowed_actions JSON NULL,
 is_allowed TINYINT(1) NOT NULL DEFAULT 1,
 created_by BIGINT UNSIGNED NULL,
 updated_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL,
 UNIQUE KEY restnew_user_access_unique (business_id, location_id, user_id, access_area)
);

CREATE TABLE restaurant_new_audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NOT NULL,
 location_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NULL,
 module_area VARCHAR(80) NOT NULL,
 action VARCHAR(80) NOT NULL,
 entity_type VARCHAR(120) NULL,
 entity_id BIGINT UNSIGNED NULL,
 old_values JSON NULL,
 new_values JSON NULL,
 ip_address VARCHAR(64) NULL,
 user_agent VARCHAR(500) NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL
);

CREATE TABLE restaurant_new_direct_url_blocks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 business_id BIGINT UNSIGNED NULL,
 location_id BIGINT UNSIGNED NULL,
 user_id BIGINT UNSIGNED NULL,
 route_name VARCHAR(160) NULL,
 url_path VARCHAR(255) NULL,
 required_permission VARCHAR(160) NULL,
 block_reason VARCHAR(255) NULL,
 created_at TIMESTAMP NULL,
 updated_at TIMESTAMP NULL
);
