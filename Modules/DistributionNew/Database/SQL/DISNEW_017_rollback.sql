DROP TABLE IF EXISTS disnew_epods;
DROP TABLE IF EXISTS disnew_delivery_checkpoints;
DROP TABLE IF EXISTS disnew_sync_items;
DROP TABLE IF EXISTS disnew_sync_batches;
DROP TABLE IF EXISTS disnew_offline_devices;
DROP TABLE IF EXISTS disnew_mobile_tokens;
DELETE FROM permissions WHERE name IN ('distribution_new.mobile_sync.view','distribution_new.mobile_devices.view','distribution_new.mobile_api.access','distribution_new.driver_api.access','distribution_new.customer_api.access');
