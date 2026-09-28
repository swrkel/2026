CREATE TABLE restaurant_new_table_service_requests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  table_id BIGINT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NULL,
  request_no VARCHAR(80) NOT NULL,
  request_type VARCHAR(50) NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'open',
  customer_note TEXT NULL,
  acknowledged_at TIMESTAMP NULL,
  completed_at TIMESTAMP NULL,
  assigned_staff_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_tsr_business (business_id),
  INDEX rn_tsr_status (status)
);

CREATE TABLE restaurant_new_customer_order_tracking (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NOT NULL,
  tracking_token VARCHAR(120) NOT NULL UNIQUE,
  customer_mobile VARCHAR(50) NULL,
  current_status VARCHAR(50) NOT NULL DEFAULT 'received',
  last_status_at TIMESTAMP NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  public_payload JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_cot_business (business_id),
  INDEX rn_cot_order (order_id)
);

CREATE TABLE restaurant_new_customer_experience_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NOT NULL,
  location_id BIGINT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NULL,
  table_id BIGINT UNSIGNED NULL,
  event_type VARCHAR(80) NOT NULL,
  description TEXT NULL,
  meta JSON NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX rn_cel_business (business_id),
  INDEX rn_cel_event (event_type)
);
