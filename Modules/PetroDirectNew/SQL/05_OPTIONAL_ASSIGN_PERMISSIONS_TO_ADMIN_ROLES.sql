-- OPTIONAL: Assign Petro Direct-New permissions to existing Super Admin and business Admin roles.
-- Review role names before running. Safe to rerun.
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.id, r.id
FROM `permissions` p
JOIN `roles` r ON r.guard_name = p.guard_name
WHERE p.name LIKE 'petro_direct_new.%'
  AND (r.name = 'Super Admin' OR r.name LIKE 'Admin#%')
  AND NOT EXISTS (
      SELECT 1 FROM `role_has_permissions` rhp
      WHERE rhp.permission_id = p.id AND rhp.role_id = r.id
  );
