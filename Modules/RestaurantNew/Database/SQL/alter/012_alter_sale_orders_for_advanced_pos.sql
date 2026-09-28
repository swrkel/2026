ALTER TABLE `rn_sale_orders`
  ADD COLUMN `merged_into_order_id` BIGINT UNSIGNED NULL AFTER `id`,
  ADD COLUMN `guest_count` INT UNSIGNED NULL AFTER `table_id`,
  ADD COLUMN `seat_label` VARCHAR(191) NULL AFTER `guest_count`,
  ADD COLUMN `is_split_bill` TINYINT(1) NOT NULL DEFAULT 0 AFTER `payment_status`,
  ADD COLUMN `paid_amount` DECIMAL(22,4) NOT NULL DEFAULT 0 AFTER `grand_total`;
