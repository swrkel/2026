-- Communication Hub optional performance indexes
-- Run in each tenant database. Safe to skip if indexes already exist.
-- If your MySQL version reports duplicate key name, skip that index.

ALTER TABLE `communication_hub_messages` ADD INDEX `idx_commhub_messages_business_status` (`business_id`, `status`);
ALTER TABLE `communication_hub_messages` ADD INDEX `idx_commhub_messages_created` (`created_at`);
ALTER TABLE `communication_hub_wallet_transactions` ADD INDEX `idx_commhub_wallet_tx_client` (`client_id`);
ALTER TABLE `communication_hub_wallet_transactions` ADD INDEX `idx_commhub_wallet_tx_created` (`created_at`);
ALTER TABLE `communication_hub_wallets` ADD INDEX `idx_commhub_wallets_client` (`client_id`);
