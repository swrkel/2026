-- RestaurantNew RESTNEW_020 final module records
-- Adjust module status table name if your application uses a custom name.

INSERT INTO modules_statuses (module, status, created_at, updated_at)
SELECT 'RestaurantNew', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM modules_statuses WHERE module = 'RestaurantNew');
