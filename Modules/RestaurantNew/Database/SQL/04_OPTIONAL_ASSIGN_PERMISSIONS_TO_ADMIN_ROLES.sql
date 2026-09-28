-- OPTIONAL: Assign every Restaurant-New permission to all business Admin roles.
-- The application normally gives Admin#<business_id> an administrator bypass, so this is generally not required.
-- Safe to run repeatedly.
INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id
FROM `permissions` p
JOIN `roles` r ON r.guard_name = 'web' AND r.name LIKE 'Admin#%'
WHERE p.guard_name = 'web' AND p.name LIKE 'restaurant\_new.%';
