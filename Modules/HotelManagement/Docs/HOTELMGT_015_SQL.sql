-- HOTELMGT_015_SQL.sql
-- Final UI/permission/menu audit SQL only for parcel 015.
-- Run this on each tenant database. It does not require hard-coded database names.

CREATE TABLE IF NOT EXISTS `hm_module_permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module` VARCHAR(100) NOT NULL DEFAULT 'HotelManagement',
  `permission_key` VARCHAR(150) NOT NULL,
  `label` VARCHAR(150) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hm_module_permissions_permission_key_unique` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hm_menu_registry` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `business_id` BIGINT UNSIGNED NULL,
  `business_location_id` BIGINT UNSIGNED NULL,
  `module` VARCHAR(100) NOT NULL DEFAULT 'HotelManagement',
  `menu_key` VARCHAR(120) NOT NULL,
  `label` VARCHAR(150) NOT NULL,
  `route_name` VARCHAR(150) NULL,
  `permission_key` VARCHAR(150) NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hm_menu_registry_business_id_index` (`business_id`),
  KEY `hm_menu_registry_business_location_id_index` (`business_location_id`),
  UNIQUE KEY `hm_menu_registry_scope_unique` (`business_id`,`business_location_id`,`menu_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `hm_module_permissions` (`module`,`permission_key`,`label`,`is_active`,`created_at`,`updated_at`) VALUES
('HotelManagement','hotel.view','Hotel Module Access',1,NOW(),NOW()),
('HotelManagement','hotel.dashboard.view','Dashboard View',1,NOW(),NOW()),
('HotelManagement','hotel.setup.view','Setup View',1,NOW(),NOW()),
('HotelManagement','hotel.setup.create','Setup Create',1,NOW(),NOW()),
('HotelManagement','hotel.setup.update','Setup Update',1,NOW(),NOW()),
('HotelManagement','hotel.setup.delete','Setup Delete',1,NOW(),NOW()),
('HotelManagement','hotel.rooms.view','Rooms View',1,NOW(),NOW()),
('HotelManagement','hotel.rooms.create','Rooms Create',1,NOW(),NOW()),
('HotelManagement','hotel.rooms.update','Rooms Update',1,NOW(),NOW()),
('HotelManagement','hotel.rooms.delete','Rooms Delete',1,NOW(),NOW()),
('HotelManagement','hotel.rates.view','Rates View',1,NOW(),NOW()),
('HotelManagement','hotel.reservations.view','Reservations View',1,NOW(),NOW()),
('HotelManagement','hotel.reservations.create','Reservations Create',1,NOW(),NOW()),
('HotelManagement','hotel.front_office.view','Front Office View',1,NOW(),NOW()),
('HotelManagement','hotel.checkin','Check In',1,NOW(),NOW()),
('HotelManagement','hotel.checkout','Check Out',1,NOW(),NOW()),
('HotelManagement','hotel.housekeeping.view','Housekeeping View',1,NOW(),NOW()),
('HotelManagement','hotel.maintenance.view','Maintenance View',1,NOW(),NOW()),
('HotelManagement','hotel.billing.view','Billing View',1,NOW(),NOW()),
('HotelManagement','hotel.pos.view','POS Charges View',1,NOW(),NOW()),
('HotelManagement','hotel.inventory.view','Inventory View',1,NOW(),NOW()),
('HotelManagement','hotel.crm.view','Guest CRM View',1,NOW(),NOW()),
('HotelManagement','hotel.banquets.view','Banquets View',1,NOW(),NOW()),
('HotelManagement','hotel.conference.view','Conference View',1,NOW(),NOW()),
('HotelManagement','hotel.room_service.view','Room Service View',1,NOW(),NOW()),
('HotelManagement','hotel.night_audit.view','Night Audit View',1,NOW(),NOW()),
('HotelManagement','hotel.night_audit.post','Night Audit Post',1,NOW(),NOW()),
('HotelManagement','hotel.night_audit.close','Night Audit Close',1,NOW(),NOW()),
('HotelManagement','hotel.reports.view','Reports View',1,NOW(),NOW()),
('HotelManagement','hotel.reports.export','Reports Export',1,NOW(),NOW()),
('HotelManagement','hotel.reports.snapshot','Reports Snapshot',1,NOW(),NOW()),
('HotelManagement','hotel.settings','Hotel Settings',1,NOW(),NOW())
ON DUPLICATE KEY UPDATE `label`=VALUES(`label`), `is_active`=VALUES(`is_active`), `updated_at`=NOW();

INSERT INTO `hm_menu_registry` (`business_id`,`business_location_id`,`module`,`menu_key`,`label`,`route_name`,`permission_key`,`sort_order`,`is_active`,`created_at`,`updated_at`) VALUES
(NULL,NULL,'HotelManagement','dashboard','Dashboard','hotel-management.dashboard','hotel.dashboard.view',10,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','setup','Setup','hotel-management.hotels.index','hotel.setup.view',20,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','rooms','Rooms','hotel-management.rooms.index','hotel.rooms.view',30,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','rates','Rates','hotel-management.rate-plans.index','hotel.rates.view',40,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','reservations','Reservations','hotel-management.reservations.index','hotel.reservations.view',50,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','front_office','Front Office','hotel-management.front-office.index','hotel.front_office.view',60,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','housekeeping','Housekeeping','hotel-management.housekeeping.index','hotel.housekeeping.view',70,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','maintenance','Maintenance','hotel-management.maintenance.index','hotel.maintenance.view',80,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','billing','Billing','hotel-management.billing.index','hotel.billing.view',90,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','pos','POS Charges','hotel-management.pos.index','hotel.pos.view',100,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','banquets','Banquets','hotel-management.banquets.index','hotel.banquets.view',110,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','conference','Conference','hotel-management.conference.index','hotel.conference.view',120,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','room_service','Room Service','hotel-management.room-service.index','hotel.room_service.view',130,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','night_audit','Night Audit','hotel-management.night-audit.index','hotel.night_audit.view',140,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','stores','Stores','hotel-management.inventory.index','hotel.inventory.view',150,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','guests','Guests','hotel-management.crm.index','hotel.crm.view',160,1,NOW(),NOW()),
(NULL,NULL,'HotelManagement','reports','Reports','hotel-management.reports.index','hotel.reports.view',170,1,NOW(),NOW())
ON DUPLICATE KEY UPDATE `label`=VALUES(`label`), `route_name`=VALUES(`route_name`), `permission_key`=VALUES(`permission_key`), `sort_order`=VALUES(`sort_order`), `is_active`=VALUES(`is_active`), `updated_at`=NOW();
