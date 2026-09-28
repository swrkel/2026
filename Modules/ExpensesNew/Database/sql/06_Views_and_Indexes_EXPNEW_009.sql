-- EXPNEW_009 indexes/views
CREATE OR REPLACE VIEW expnew_v_command_pending_summary AS
SELECT business_id, location_id, status, COUNT(*) total_records, SUM(amount) total_amount
FROM expnew_approval_queues
GROUP BY business_id, location_id, status;
