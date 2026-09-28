-- OPTIONAL HOST-APPLICATION HELPER ONLY.
-- Run this ONLY if your application really has a `modules_statuses` table with these columns.
INSERT INTO `modules_statuses` (`module`, `status`, `created_at`, `updated_at`)
SELECT 'RestaurantNew', 1, NOW(), NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM `modules_statuses` WHERE `module` = 'RestaurantNew'
);
