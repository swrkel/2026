INSERT INTO permissions (name, guard_name, created_at, updated_at) VALUES
('restaurantnew.super_admin.features', 'web', NOW(), NOW()),
('restaurantnew.super_admin.user_access', 'web', NOW(), NOW()),
('restaurantnew.super_admin.audit_logs', 'web', NOW(), NOW()),
('restaurantnew.security.direct_url_protection', 'web', NOW(), NOW())
ON DUPLICATE KEY UPDATE updated_at = VALUES(updated_at);
