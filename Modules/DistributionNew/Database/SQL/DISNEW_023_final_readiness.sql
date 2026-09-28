-- DISNEW_023 Final Readiness SQL
-- Prefix: disnew_

CREATE TABLE IF NOT EXISTS disnew_final_readiness_checks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  location_id BIGINT UNSIGNED NULL,
  check_code VARCHAR(100) NOT NULL,
  check_name VARCHAR(191) NOT NULL,
  check_group VARCHAR(100) NOT NULL DEFAULT 'general',
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  severity VARCHAR(30) NOT NULL DEFAULT 'info',
  message TEXT NULL,
  checked_by BIGINT UNSIGNED NULL,
  checked_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX idx_disnew_frc_business (business_id),
  INDEX idx_disnew_frc_group (check_group),
  INDEX idx_disnew_frc_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disnew_installation_verifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  business_id BIGINT UNSIGNED NULL,
  verification_key VARCHAR(120) NOT NULL,
  verification_value TEXT NULL,
  result VARCHAR(30) NOT NULL DEFAULT 'pending',
  remarks TEXT NULL,
  verified_by BIGINT UNSIGNED NULL,
  verified_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uq_disnew_install_key_business (business_id, verification_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
