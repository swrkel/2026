-- Management Report Module - TENANT DATABASE ALTER TABLES
-- No existing ERP or central database table is altered.
-- All module-owned tables use the mgmt_ prefix and live in each tenant database.
SELECT DATABASE() AS `tenant_database`,
       'No legacy table alterations required' AS `message`;
