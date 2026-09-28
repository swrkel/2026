-- OPTIONAL: Give every Stock Taking - New permission to each business Admin role.
-- The application's Gate already gives Admin#<business_id> unrestricted access, so this is normally not required.
-- Safe to run repeatedly.
INSERT IGNORE INTO `role_has_permissions` (`permission_id`,`role_id`)
SELECT p.id, r.id
FROM `permissions` p
JOIN `roles` r ON r.guard_name='web' AND r.name LIKE 'Admin#%'
WHERE p.guard_name='web' AND p.name LIKE 'stock_taking_new.%';
