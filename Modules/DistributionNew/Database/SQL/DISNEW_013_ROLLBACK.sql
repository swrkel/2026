-- DISNEW_013 rollback. Run only if Stage 13 must be removed.
DROP TABLE IF EXISTS `disnew_trip_commissions`;
DROP TABLE IF EXISTS `disnew_trip_expenses`;
DROP TABLE IF EXISTS `disnew_vehicle_documents`;
DROP TABLE IF EXISTS `disnew_vehicle_maintenances`;
DROP TABLE IF EXISTS `disnew_vehicle_fuel_entries`;
DROP TABLE IF EXISTS `disnew_vehicle_odometer_histories`;
DROP TABLE IF EXISTS `disnew_helpers`;
DROP TABLE IF EXISTS `disnew_drivers`;
DELETE FROM `permissions` WHERE `name` IN (
'distributionnew.drivers.view','distributionnew.drivers.create','distributionnew.drivers.update',
'distributionnew.helpers.view','distributionnew.helpers.create','distributionnew.helpers.update',
'distributionnew.fuel.view','distributionnew.fuel.create','distributionnew.odometer.view','distributionnew.odometer.create',
'distributionnew.maintenance.view','distributionnew.maintenance.create','distributionnew.vehicle_documents.view','distributionnew.vehicle_documents.create',
'distributionnew.trip_expenses.view','distributionnew.trip_expenses.create','distributionnew.trip_commissions.view','distributionnew.trip_commissions.approve'
);
