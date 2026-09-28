-- S683 - restore module visibility for roles saved without the managed marker
--
--
-- WHAT WENT WRONG
--
-- A role was given Petro PD rights in User Management, but the user holding it
-- saw only the Finance module.
--
-- SidebarPermissionUtil builds a user's module access from the `umn.`
-- permissions on their roles - but it SKIPS any role that does not also hold the
-- marker permission `umn.managed`:
--
--     if (!in_array('umn.managed', $names, true)) { continue; }
--
-- That marker is what tells the sidebar "this role's module rights are managed
-- in User Management, honour them". Without it the role is passed over
-- completely: every module grant on it is ignored and the user falls back to
-- whatever legacy access remains.
--
-- RolePermissionService defined the constant (self::MANAGED = 'umn.managed') and
-- included it when BUILDING a selection, but nothing guaranteed it survived to
-- the save. Any role saved from a form that did not submit it - or from a
-- selection assembled elsewhere - ended up with module rights the sidebar could
-- not read.
--
-- The accompanying module update adds the marker unconditionally at the point of
-- writing, so this cannot happen again. This script repairs the roles already
-- saved without it.
--
--
-- WHAT IT DOES
--
-- Adds `umn.managed` to every role that already holds at least one other `umn.`
-- permission - in other words, every role that WAS configured in User Management
-- and is currently being ignored.
--
-- Roles with no `umn.` permissions are left alone: they were never managed here,
-- and adding the marker would suddenly subject them to module filtering they
-- have never been configured for. That would REMOVE access rather than restore
-- it, which is the opposite of the intent.
--
-- Safe to run more than once - a role that already has the marker is skipped.
--
-- RUN ONCE PER TENANT DATABASE, then clear the permission cache:
--
--     php artisan permission:cache-reset
--     php artisan tenants:run permission:cache-reset --tenants=TENANT_ID
--
-- The second form is needed on a tenant database: the plain command clears the
-- CENTRAL cache, not the tenant's.


-- ---------------------------------------------------------------------------
-- STEP 1 - which roles are affected (run this first)
-- ---------------------------------------------------------------------------
--
-- Every row returned is a role whose module rights are currently ignored.

SELECT r.`id`,
       r.`name`,
       COUNT(p.`id`) AS umn_permissions
  FROM `roles` r
  JOIN `role_has_permissions` rhp ON rhp.`role_id` = r.`id`
  JOIN `permissions` p            ON p.`id` = rhp.`permission_id`
 WHERE p.`name` LIKE 'umn.%'
   AND NOT EXISTS (
        SELECT 1
          FROM `role_has_permissions` rhp2
          JOIN `permissions` p2 ON p2.`id` = rhp2.`permission_id`
         WHERE rhp2.`role_id` = r.`id`
           AND p2.`name` = 'umn.managed'
   )
 GROUP BY r.`id`, r.`name`
 ORDER BY r.`id`;


-- ---------------------------------------------------------------------------
-- STEP 2 - make sure the marker permission itself exists
-- ---------------------------------------------------------------------------

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT 'umn.managed', 'web', NOW(), NOW()
 WHERE NOT EXISTS (
        SELECT 1 FROM `permissions`
         WHERE `name` = 'umn.managed'
           AND `guard_name` = 'web'
 );


-- ---------------------------------------------------------------------------
-- STEP 3 - grant it to the affected roles
-- ---------------------------------------------------------------------------

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT m.`id`, r.`id`
  FROM `roles` r
  JOIN `permissions` m
    ON m.`name` = 'umn.managed'
   AND m.`guard_name` = 'web'
 WHERE EXISTS (
        SELECT 1
          FROM `role_has_permissions` rhp
          JOIN `permissions` p ON p.`id` = rhp.`permission_id`
         WHERE rhp.`role_id` = r.`id`
           AND p.`name` LIKE 'umn.%'
   )
   AND NOT EXISTS (
        SELECT 1
          FROM `role_has_permissions` rhp2
         WHERE rhp2.`role_id` = r.`id`
           AND rhp2.`permission_id` = m.`id`
   );


-- ---------------------------------------------------------------------------
-- STEP 4 - verify
-- ---------------------------------------------------------------------------
--
-- Re-run STEP 1. It should now return no rows.
--
-- Then clear the permission cache and log in as the affected user. The modules
-- granted on their role should appear.
