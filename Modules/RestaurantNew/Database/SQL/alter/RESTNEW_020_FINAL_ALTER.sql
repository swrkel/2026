-- RESTNEW 020 corrected final safeguards for a fresh consolidated install.
ALTER TABLE `restaurant_new_orders`
  ADD COLUMN `kitchen_print_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `order_status`;
ALTER TABLE `restaurant_new_kitchen_tickets`
  ADD COLUMN `bill_printed_at` TIMESTAMP NULL AFTER `printed_at`,
  ADD COLUMN `last_status_changed_at` TIMESTAMP NULL AFTER `status`;
