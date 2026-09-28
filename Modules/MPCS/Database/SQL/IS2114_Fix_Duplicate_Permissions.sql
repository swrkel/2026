-- IS2114 - remove duplicate permission names, without losing any grant
--
--
-- WHAT IS WRONG
--
-- The permissions table holds the same name more than once. On the tenant
-- inspected:
--
--     id 565  add_dividends        id 571  add_dividends
--     id 566  edit_dividends       id 572  edit_dividends
--
-- spatie/laravel-permission resolves a permission by NAME and expects a single
-- row. With two rows sharing a name its lookups and its cache become
-- unreliable, and that is a plausible contributor to the retry loop behind the
-- "Maximum execution time of 900 seconds exceeded at
-- .../PermissionDoesNotExist.php:11" entries in the log.
--
--
-- WHY THIS CANNOT BE A SIMPLE DELETE
--
-- Roles and users are attached to a permission by ID, not by name. Deleting the
-- higher-numbered duplicate silently removes every grant pointing at it - a role
-- that held `edit_dividends` through id 572 would simply lose it.
--
-- So the grants are moved onto the surviving id FIRST, and only then are the
-- duplicate rows removed.
--
--
-- ORDER OF OPERATIONS, AND WHY
--
--   1. Work out the survivor for each duplicated name - the LOWEST id, which is
--      the original row.
--
--   2. DELETE grants that would COLLIDE. role_has_permissions is keyed on
--      (permission_id, role_id). If a role is attached to BOTH ids, re-pointing
--      the second one would violate that key and the statement would fail. The
--      role already holds the permission through the survivor, so dropping the
--      redundant row loses nothing.
--
--   3. UPDATE the remaining grants onto the survivor.
--
--   4. DELETE the now-unreferenced duplicate permission rows.
--
-- Steps 2 and 3 are done for role_has_permissions and again for
-- model_has_permissions, which attaches permissions directly to users.
--
-- A temporary table is used because MySQL will not let a statement update a
-- table it is also selecting from in a subquery (error 1093).
--
--
-- SAFE TO RE-RUN. After the first pass there are no duplicates left to find.
--
-- RUN ONCE PER TENANT DATABASE, then:  php artisan permission:cache-reset
--
--
-- TAKE A BACKUP FIRST. This edits permission assignments.
--
--     mysqldump -u USER -p DBNAME permissions role_has_permissions model_has_permissions \
--         > permissions_backup.sql


-- ---------------------------------------------------------------------------
-- STEP 0 - what will change (run this on its own first)
-- ---------------------------------------------------------------------------
--
--     SELECT name, guard_name, COUNT(*) AS copies, GROUP_CONCAT(id ORDER BY id) AS ids
--       FROM permissions
--      GROUP BY name, guard_name
--     HAVING copies > 1;
--
-- If that returns no rows, there is nothing to do and you can stop here.


-- ---------------------------------------------------------------------------
-- STEP 1 - map each duplicate to the id that will survive
-- ---------------------------------------------------------------------------

DROP TEMPORARY TABLE IF EXISTS `tmp_permission_dupes`;

CREATE TEMPORARY TABLE `tmp_permission_dupes` (
    `dupe_id`     BIGINT UNSIGNED NOT NULL,
    `survivor_id` BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (`dupe_id`),
    KEY `survivor_idx` (`survivor_id`)
) ENGINE=InnoDB;

INSERT INTO `tmp_permission_dupes` (`dupe_id`, `survivor_id`)
SELECT p.`id`, s.`survivor_id`
  FROM `permissions` p
  JOIN (
        SELECT `name`, `guard_name`, MIN(`id`) AS `survivor_id`
          FROM `permissions`
         GROUP BY `name`, `guard_name`
        HAVING COUNT(*) > 1
       ) s
    ON s.`name` = p.`name`
   AND s.`guard_name` = p.`guard_name`
 WHERE p.`id` <> s.`survivor_id`;

-- Check what was matched before going further:
--     SELECT * FROM tmp_permission_dupes;


-- ---------------------------------------------------------------------------
-- STEP 2 - role grants
-- ---------------------------------------------------------------------------

-- 2a. Drop grants that would collide: the role already holds the survivor.
DELETE rhp
  FROM `role_has_permissions` rhp
  JOIN `tmp_permission_dupes` d
    ON d.`dupe_id` = rhp.`permission_id`
 WHERE EXISTS (
       SELECT 1
         FROM (SELECT `permission_id`, `role_id` FROM `role_has_permissions`) k
        WHERE k.`permission_id` = d.`survivor_id`
          AND k.`role_id` = rhp.`role_id`
 );

-- 2b. Move the rest onto the survivor.
UPDATE `role_has_permissions` rhp
  JOIN `tmp_permission_dupes` d
    ON d.`dupe_id` = rhp.`permission_id`
   SET rhp.`permission_id` = d.`survivor_id`;


-- ---------------------------------------------------------------------------
-- STEP 3 - permissions attached directly to users
-- ---------------------------------------------------------------------------

-- 3a. Drop collisions, keyed on (permission_id, model_type, model_id).
DELETE mhp
  FROM `model_has_permissions` mhp
  JOIN `tmp_permission_dupes` d
    ON d.`dupe_id` = mhp.`permission_id`
 WHERE EXISTS (
       SELECT 1
         FROM (SELECT `permission_id`, `model_type`, `model_id` FROM `model_has_permissions`) k
        WHERE k.`permission_id` = d.`survivor_id`
          AND k.`model_type` = mhp.`model_type`
          AND k.`model_id` = mhp.`model_id`
 );

-- 3b. Move the rest onto the survivor.
UPDATE `model_has_permissions` mhp
  JOIN `tmp_permission_dupes` d
    ON d.`dupe_id` = mhp.`permission_id`
   SET mhp.`permission_id` = d.`survivor_id`;


-- ---------------------------------------------------------------------------
-- STEP 4 - remove the duplicate permission rows
-- ---------------------------------------------------------------------------
--
-- Nothing references them now: every grant was either moved or was redundant.

DELETE p
  FROM `permissions` p
  JOIN `tmp_permission_dupes` d
    ON d.`dupe_id` = p.`id`;

DROP TEMPORARY TABLE IF EXISTS `tmp_permission_dupes`;


-- ---------------------------------------------------------------------------
-- STEP 5 - verify
-- ---------------------------------------------------------------------------
--
-- a) No duplicates remain - expect zero rows:
--
--     SELECT name, guard_name, COUNT(*) c
--       FROM permissions
--      GROUP BY name, guard_name
--     HAVING c > 1;
--
-- b) No grant points at a permission that no longer exists - expect zero:
--
--     SELECT COUNT(*) AS orphaned_role_grants
--       FROM role_has_permissions rhp
--       LEFT JOIN permissions p ON p.id = rhp.permission_id
--      WHERE p.id IS NULL;
--
--     SELECT COUNT(*) AS orphaned_user_grants
--       FROM model_has_permissions mhp
--       LEFT JOIN permissions p ON p.id = mhp.permission_id
--      WHERE p.id IS NULL;
--
-- c) The roles still hold what they held. Compare this against the same query
--    run BEFORE the script - the lists should be identical:
--
--     SELECT r.name AS role, p.name AS permission
--       FROM role_has_permissions rhp
--       JOIN roles r       ON r.id = rhp.role_id
--       JOIN permissions p ON p.id = rhp.permission_id
--      ORDER BY r.name, p.name;
--
-- Finally, clear the cache or the old rows are still served from it:
--
--     php artisan permission:cache-reset
