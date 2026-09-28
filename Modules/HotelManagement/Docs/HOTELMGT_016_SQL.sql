/* HOTELMGT_016_SQL.sql
   Hotel Management Parcel 016 only.
   Purpose: add testing/readiness menu and permission registry records for the System Check page.
*/

INSERT INTO `hm_module_permissions` (`module`,`permission_key`,`label`,`is_active`,`created_at`,`updated_at`) VALUES
('HotelManagement','hotel.system_check.view','Hotel System Check View',1,NOW(),NOW())
ON DUPLICATE KEY UPDATE `label`=VALUES(`label`), `is_active`=VALUES(`is_active`), `updated_at`=NOW();

INSERT INTO `hm_menu_registry` (`business_id`,`business_location_id`,`module`,`menu_key`,`label`,`route_name`,`permission_key`,`sort_order`,`is_active`,`created_at`,`updated_at`) VALUES
(NULL,NULL,'HotelManagement','system_check','System Check','hotel-management.system-check.index','hotel.settings',180,1,NOW(),NOW())
ON DUPLICATE KEY UPDATE `label`=VALUES(`label`), `route_name`=VALUES(`route_name`), `permission_key`=VALUES(`permission_key`), `sort_order`=VALUES(`sort_order`), `is_active`=VALUES(`is_active`), `updated_at`=NOW();
