-- Stock Transfer-New final index SQL
-- Tenant database only. Add indexes according to actual DB engine support.

CREATE INDEX stn_installation_checks_status_idx ON stn_installation_checks (check_status);
CREATE INDEX stn_transfers_final_checked_idx ON stn_transfers (business_id, final_checked_at);
CREATE INDEX stn_transfer_lines_variance_note_idx ON stn_transfer_lines (transfer_id);
