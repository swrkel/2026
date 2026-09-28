-- ATN-087 TO ATN-094 PERFORMANCE INDEXES
ALTER TABLE `atn_tickets`
    ADD INDEX `atn_tickets_perf_idx` (`business_id`,`business_location_id`,`store_id`,`issue_date`,`status`);

ALTER TABLE `atn_reservations`
    ADD INDEX `atn_reservations_perf_idx` (`business_id`,`business_location_id`,`store_id`,`reservation_date`,`status`);

ALTER TABLE `atn_invoices`
    ADD INDEX `atn_invoices_perf_idx` (`business_id`,`invoice_date`,`status`,`due_total`);

ALTER TABLE `atn_operational_tasks`
    ADD INDEX `atn_operational_tasks_perf_idx` (`business_id`,`assigned_to`,`status`,`due_at`);
