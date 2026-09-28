-- Stock Taking - New installation verification
SELECT table_name
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name LIKE 'stk\_%'
ORDER BY table_name;

SELECT name, guard_name
FROM permissions
WHERE name LIKE 'stock_taking_new.%'
ORDER BY name;
