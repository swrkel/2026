-- Execute only when indexes do not already exist.
ALTER TABLE `atn_tickets` ADD INDEX `atn_final_ticket_lookup_idx` (`business_id`,`business_location_id`,`store_id`,`ticket_no`,`issue_date`,`status`);
ALTER TABLE `atn_reservations` ADD INDEX `atn_final_reservation_lookup_idx` (`business_id`,`business_location_id`,`store_id`,`reservation_no`,`reservation_date`,`status`);
ALTER TABLE `atn_payments` ADD INDEX `atn_final_payment_lookup_idx` (`business_id`,`payment_date`,`payment_method`,`status`);
ALTER TABLE `atn_refunds` ADD INDEX `atn_final_refund_lookup_idx` (`business_id`,`request_date`,`status`,`ticket_id`);
