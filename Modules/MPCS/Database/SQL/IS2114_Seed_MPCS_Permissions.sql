-- IS2114 - MPCS permissions: seed the missing rows
--
--
-- WHAT IS WRONG
--
-- Every MPCS form guards itself with a permission check, for example:
--
--     if (!auth()->user()->can('f10_form')) {
--         abort(403, 'Unauthorized action.');
--     }
--
-- Those permissions do not exist in the `permissions` table. `f10_form` was
-- confirmed absent while `dashboard.data` was present, so this is not a role
-- assignment problem - the permission itself has never been created.
--
-- Modules/MPCS/Database/Seeders/MPCSDatabaseSeeder.php is empty: it calls
-- Model::unguard() and nothing else. The module has therefore never seeded its
-- own permissions on any tenant, which is why a NEW database shows the same
-- faults as the old one.
--
--
-- WHY IT PRODUCES THREE DIFFERENT SYMPTOMS
--
--   403 Unauthorized  - a permission that does not exist can never be granted,
--                       so the guard fails for every user including admins.
--   Blank page        - the same check inside a view hides the content while
--                       the layout still renders, leaving just the header.
--   Cloudflare 524    - spatie throws PermissionDoesNotExist, which the
--                       application retries; the request spins until PHP's
--                       900-second limit, long past Cloudflare's 100-second
--                       ceiling. This matches the
--                       "Maximum execution time of 900 seconds exceeded at
--                       .../PermissionDoesNotExist.php:11" entries in the log.
--
--
-- WHAT THIS SCRIPT DOES
--
-- Step 1 creates the 26 MPCS permissions if they are missing.
-- Step 2 grants them to the business admin roles.
--
-- Both steps are idempotent - an existing permission is left alone, and a grant
-- that is already in place is not duplicated. Safe to run more than once.
--
-- RUN ONCE PER TENANT DATABASE, then:  php artisan permission:cache-reset
--
--
-- BEFORE YOU START - see what is missing
--
--     SELECT COUNT(*) FROM permissions WHERE name LIKE 'f%_form';
--
-- On a broken tenant this returns 0.


-- ---------------------------------------------------------------------------
-- STEP 1 - create the permissions
-- ---------------------------------------------------------------------------
--
-- guard_name is 'web', matching every other permission in this application.
-- INSERT ... SELECT ... WHERE NOT EXISTS creates only what is absent.

INSERT INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`)
SELECT t.name, 'web', NOW(), NOW()
FROM (
    SELECT 'f9a_form'                     AS name UNION ALL
    SELECT 'f9a_settings_form'                    UNION ALL
    SELECT 'f9c_form'                             UNION ALL
    SELECT 'f9c_cash_form'                        UNION ALL
    SELECT 'f9c_settings_form'                    UNION ALL
    SELECT 'f10_form'                             UNION ALL
    SELECT 'f10_form_cash_given_button'           UNION ALL
    SELECT 'f10_form_cash_received_button'        UNION ALL
    SELECT 'f14_form'                             UNION ALL
    SELECT 'f14b_form'                            UNION ALL
    SELECT 'f15_form'                             UNION ALL
    SELECT 'f15_settings_form'                    UNION ALL
    SELECT 'f15a9abc_form'                        UNION ALL
    SELECT '15_form'                              UNION ALL
    SELECT 'f16_form'                             UNION ALL
    SELECT 'f16a_form'                            UNION ALL
    SELECT '16a_form'                             UNION ALL
    SELECT 'f17_form'                             UNION ALL
    SELECT 'edit_f17_form'                        UNION ALL
    SELECT 'f18_form'                             UNION ALL
    SELECT 'f20_form'                             UNION ALL
    SELECT 'f21_form'                             UNION ALL
    SELECT 'f21c_form'                            UNION ALL
    SELECT 'f22_stock_taking_form'                UNION ALL
    SELECT 'approve_f22_stock_taking'             UNION ALL
    SELECT 'f25_form'                             UNION ALL
    SELECT 'mpcs_form_settings'                   UNION ALL
    SELECT 'list_opening_values'
) AS t
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` p
     WHERE p.`name` = t.name
       AND p.`guard_name` = 'web'
);


-- ---------------------------------------------------------------------------
-- STEP 2 - grant them to the business admin roles
-- ---------------------------------------------------------------------------
--
-- Every role named 'Admin#<business_id>' receives all of the above. Adjust the
-- role filter if only certain businesses should get MPCS.
--
-- To grant to ONE role instead, replace the roles sub-select with its id, e.g.
--     AND r.id = 2

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT p.`id`, r.`id`
  FROM `permissions` p
  JOIN `roles` r
    ON r.`name` LIKE 'Admin#%'
 WHERE p.`name` IN (
    'f9a_form','f9a_settings_form','f9c_form','f9c_cash_form','f9c_settings_form',
    'f10_form','f10_form_cash_given_button','f10_form_cash_received_button',
    'f14_form','f14b_form','f15_form','f15_settings_form','f15a9abc_form','15_form',
    'f16_form','f16a_form','16a_form','f17_form','edit_f17_form','f18_form',
    'f20_form','f21_form','f21c_form','f22_stock_taking_form',
    'approve_f22_stock_taking','f25_form','mpcs_form_settings','list_opening_values'
 )
   AND NOT EXISTS (
       SELECT 1 FROM `role_has_permissions` rhp
        WHERE rhp.`permission_id` = p.`id`
          AND rhp.`role_id` = r.`id`
   );


-- ---------------------------------------------------------------------------
-- STEP 3 - verify
-- ---------------------------------------------------------------------------
--
--     SELECT COUNT(*) AS mpcs_permissions
--       FROM permissions
--      WHERE name IN ('f9a_form','f10_form','f16a_form','f21c_form','f25_form');
--     -- expect 5
--
--     SELECT r.name, COUNT(*) AS granted
--       FROM role_has_permissions rhp
--       JOIN roles r ON r.id = rhp.role_id
--      WHERE r.name LIKE 'Admin#%'
--      GROUP BY r.name;
--
-- Then clear the cache, or the old empty result is served from it:
--
--     php artisan permission:cache-reset
--
--
-- ---------------------------------------------------------------------------
-- SEPARATE PROBLEM - duplicate permission names
-- ---------------------------------------------------------------------------
--
-- The tenant inspected also had `add_dividends` and `edit_dividends` twice
-- (ids 565/571 and 566/572). Spatie resolves a permission by name and expects
-- one row; duplicates make its lookup and cache unreliable and may be part of
-- the retry loop behind the 900-second timeouts.
--
-- Find them:
--
--     SELECT name, COUNT(*) c, GROUP_CONCAT(id) ids
--       FROM permissions
--      GROUP BY name, guard_name
--     HAVING c > 1;
--
-- Do not delete blindly - re-point role_has_permissions and
-- model_has_permissions at the surviving id first, or roles will lose grants.
