-- OPTIONAL: grant every Petro PD-New permission to standard administrative roles.
-- Review the role names before running. Safe to rerun because INSERT IGNORE is used.
INSERT IGNORE INTO `role_has_permissions` (`permission_id`,`role_id`)
SELECT p.id, r.id
FROM `permissions` p
JOIN `roles` r
  ON r.name IN ('Admin','Business Admin','Super Admin')
 AND r.guard_name = p.guard_name
WHERE p.name LIKE 'petro_pd_new.%'
  AND p.guard_name = 'web';
